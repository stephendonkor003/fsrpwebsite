<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Event;
use App\Models\EventResource;
use App\Models\Slide;
use Closure;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Throwable;

class ContentController extends Controller
{
    public function index(string $type): View
    {
        $definition = $this->definition($type);
        $query = $definition['model']::query();

        if (in_array($type, ['sessions', 'resources'], true)) {
            $query->with('event');
        }

        $query->orderBy($definition['order_by'] ?? 'created_at', $definition['order_direction'] ?? 'asc');

        return view('admin.content.index', [
            'type' => $type,
            'definition' => $definition,
            'items' => $query->paginate(20),
        ]);
    }

    public function create(string $type): View
    {
        $definition = $this->definition($type);
        $modelClass = $definition['model'];

        return $this->formView($type, $definition, new $modelClass);
    }

    public function store(Request $request, string $type): RedirectResponse
    {
        $definition = $this->definition($type);
        $modelClass = $definition['model'];
        $item = new $modelClass;

        $this->saveRecord($request, $definition, $item);

        return redirect()->route('admin.content.index', $type)->with('status', $definition['singular'].' created successfully.');
    }

    public function edit(string $type, string $item): View
    {
        $definition = $this->definition($type);
        $record = $this->findRecord($definition, $item);

        return $this->formView($type, $definition, $record);
    }

    public function update(Request $request, string $type, string $item): RedirectResponse
    {
        $definition = $this->definition($type);
        $record = $this->findRecord($definition, $item);

        $this->saveRecord($request, $definition, $record);

        return redirect()->route('admin.content.index', $type)->with('status', $definition['singular'].' updated successfully.');
    }

    public function destroy(string $type, string $item): RedirectResponse
    {
        $definition = $this->definition($type);
        $record = $this->findRecord($definition, $item);

        $resourcePaths = [];
        DB::transaction(function () use ($record, &$resourcePaths): void {
            $record = $record->newQuery()->lockForUpdate()->findOrFail($record->getKey());

            if ($record instanceof Event) {
                foreach ($record->resources()->lockForUpdate()->get() as $resource) {
                    $resourcePaths[] = $resource->file_path;
                    $resource->delete();
                }
                $record->sessions()->update(['is_published' => false]);
            } elseif ($record instanceof EventResource) {
                $resourcePaths[] = $record->file_path;
            }

            $record->delete();
        });

        foreach ($resourcePaths as $path) {
            Storage::disk('local')->delete($path);
        }
        $this->deleteManagedImage((string) $record->getAttribute('image'));

        return redirect()->route('admin.content.index', $type)->with('status', $definition['singular'].' deleted.');
    }

    /**
     * @return array<string, mixed>
     */
    private function definition(string $type): array
    {
        $definition = config("admin-content.types.$type");
        abort_unless(is_array($definition), 404);

        return $definition;
    }

    /**
     * @param  array<string, mixed>  $definition
     */
    private function findRecord(array $definition, string $item): Model
    {
        abort_unless(filter_var($item, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]) !== false, 404);

        return $definition['model']::query()->findOrFail($item);
    }

    /**
     * @param  array<string, mixed>  $definition
     */
    private function formView(string $type, array $definition, Model $item): View
    {
        return view('admin.content.form', [
            'type' => $type,
            'definition' => $definition,
            'item' => $item,
            'locales' => config('locales.supported'),
            'events' => Event::orderBy('start_at')->get(),
        ]);
    }

    /**
     * @param  array<string, mixed>  $definition
     * @return array<string, mixed>
     */
    private function payload(Request $request, array $definition, Model $item): array
    {
        $rules = [];
        $locales = array_keys(config('locales.supported'));

        foreach ($definition['fields'] as $field) {
            $name = $field['name'];

            if ($field['translatable'] ?? false) {
                foreach ($locales as $locale) {
                    $rules["translations.$locale.$name"] = [
                        ($field['required'] ?? false) && $locale === 'en' ? 'required' : 'nullable',
                        'string',
                        $field['type'] === 'richtext' || $field['type'] === 'textarea' ? 'max:20000' : 'max:500',
                    ];
                }

                continue;
            }

            $rules[$name] = $this->rulesForField($field, $item);

            if ($name === 'end_at') {
                $rules[$name][] = 'after_or_equal:start_at';
            }
        }

        $validated = $request->validate($rules);
        $payload = [];

        foreach ($definition['fields'] as $field) {
            $name = $field['name'];

            if ($field['translatable'] ?? false) {
                $translations = [];
                foreach ($locales as $locale) {
                    $translations[$locale] = trim((string) Arr::get($validated, "translations.$locale.$name", ''));
                }
                $payload[$name] = $translations;

                continue;
            }

            if ($field['type'] === 'document') {
                continue;
            }

            if ($field['type'] === 'boolean') {
                $payload[$name] = $request->boolean($name);
            } elseif ($field['type'] === 'image') {
                if ($request->hasFile($name)) {
                    $this->deleteManagedImage((string) $item->getAttribute($name));
                    $payload[$name] = '/storage/'.$request->file($name)->store('content', 'public');
                } elseif ($item->exists) {
                    $payload[$name] = $item->getAttribute($name);
                }
            } else {
                $payload[$name] = $validated[$name] ?? $field['default'] ?? null;
            }
        }

        if (array_key_exists('slug', $payload) && blank($payload['slug'])) {
            $payload['slug'] = Str::slug((string) Arr::get($validated, 'translations.en.title', Str::random(8)));
        }

        return $payload;
    }

    /**
     * @param  array<string, mixed>  $field
     * @return array<int, mixed>
     */
    private function rulesForField(array $field, Model $item): array
    {
        $required = ($field['required'] ?? false) ? 'required' : 'nullable';

        if ($field['name'] === 'key') {
            return [$required, 'alpha_dash', 'max:120', Rule::unique($item->getTable(), 'key')->ignore($item->getKey())];
        }

        return match ($field['type']) {
            'boolean' => ['nullable', 'boolean'],
            'number' => [$required, 'integer', 'min:0', 'max:10000'],
            'datetime' => [$required, 'date'],
            'url' => [$required, 'url:http,https', 'max:2048'],
            'link' => ['bail', 'nullable', 'string', 'max:2048', function (string $attribute, mixed $value, Closure $fail): void {
                if (! Slide::isSafeButtonUrl($value)) {
                    $fail('Use an HTTP or HTTPS URL, or a local page path beginning with a single slash.');
                }
            }],
            'video' => ['bail', 'nullable', 'string', 'max:2048', function (string $attribute, mixed $value, Closure $fail): void {
                if (preg_match('#^/videos/fsrp/[a-zA-Z0-9_-]+\.(mp4|webm)$#', $value)) {
                    return;
                }

                if (! filter_var($value, FILTER_VALIDATE_URL) || ! in_array(strtolower((string) parse_url($value, PHP_URL_SCHEME)), ['http', 'https'], true)) {
                    $fail('Use an HTTP or HTTPS video URL, or a managed /videos/fsrp/ video path.');
                }
            }],
            'document' => [$item->exists ? 'nullable' : 'required', 'file', 'mimes:pdf,docx,xlsx', 'extensions:pdf,docx,xlsx', 'max:20480'],
            'image' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:6144'],
            'event' => ['nullable', 'integer', 'exists:events,id'],
            'select' => [$required, Rule::in(array_keys($field['options']))],
            'slug' => [$required, 'regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/', 'max:180', Rule::unique($item->getTable(), 'slug')->ignore($item->getKey())],
            default => [$required, 'string', 'max:2048'],
        };
    }

    private function deleteManagedImage(string $image): void
    {
        if (Str::startsWith($image, '/storage/')) {
            Storage::disk('public')->delete(Str::after($image, '/storage/'));
        }
    }

    /**
     * @param  array<string, mixed>  $definition
     */
    private function saveRecord(Request $request, array $definition, Model $item): void
    {
        $payload = $this->payload($request, $definition, $item);

        if (! $item instanceof EventResource) {
            $item->fill($payload)->save();

            return;
        }

        $newPath = null;
        $previousPath = null;

        try {
            if ($request->hasFile('document')) {
                $file = $request->file('document');
                $newPath = $file->store('event-resources', 'local');

                if ($newPath === false) {
                    throw ValidationException::withMessages(['document' => 'The document could not be stored. Please try again.']);
                }

                $payload = array_merge($payload, [
                    'file_path' => $newPath,
                    'original_filename' => Str::limit(preg_replace('/[\x00-\x1F\x7F]/u', '', $file->getClientOriginalName()), 250, ''),
                    'mime_type' => $file->getMimeType(),
                    'file_size' => $file->getSize(),
                ]);
            }

            DB::transaction(function () use ($item, $payload, $newPath, &$previousPath): void {
                if ($item->exists) {
                    $item = EventResource::lockForUpdate()->findOrFail($item->getKey());
                    $previousPath = $newPath ? $item->file_path : null;
                }

                $item->fill($payload)->save();
            });
        } catch (Throwable $exception) {
            if ($newPath) {
                Storage::disk('local')->delete($newPath);
            }

            throw $exception;
        }

        if ($previousPath) {
            Storage::disk('local')->delete($previousPath);
        }
    }
}
