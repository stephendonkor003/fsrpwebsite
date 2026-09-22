<?php

$unverifiedRetentionDays = max(1, (int) env('SEED_SUMMIT_UNVERIFIED_RETENTION_DAYS', 30));
$retentionDaysAfterEvent = max(1, (int) env('SEED_SUMMIT_RETENTION_DAYS_AFTER_EVENT', 365));

return [
    'event_slug' => 'inaugural-seed-investment-summit',
    'theme' => 'Resilient Seed Systems for a Food Secure Africa',
    'consent_version' => '2026-09-21-v2',
    'receipt_link_minutes' => 30,
    'email_verification_days' => 7,
    'email_send_lease_seconds' => max(
        200,
        (int) env('SEED_SUMMIT_EMAIL_SEND_LEASE_SECONDS', 200),
    ),
    'email_dispatch_stale_seconds' => max(
        201,
        (int) env('SEED_SUMMIT_EMAIL_DISPATCH_STALE_SECONDS', 300),
    ),
    'unverified_retention_days' => $unverifiedRetentionDays,
    'retention_days_after_event' => $retentionDaysAfterEvent,
    'data_protection_notice' => sprintf(
        'The information in this form, including identity and travel documents, will be available only to authorised summit administration personnel and used for delegate registration, accreditation, security, travel, hospitality, and event communications. It may be shared with authorised event partners only where operationally necessary. Registrations whose official email remains unverified are deleted after %d days. Other registration records and encrypted uploads are deleted %d days after the event ends. Do not submit unrelated sensitive information.',
        $unverifiedRetentionDays,
        $retentionDaysAfterEvent,
    ),

    'titles' => ['Mr', 'Mrs', 'Ms', 'Dr', 'Prof', 'Hon', 'Amb', 'Rev'],

    'genders' => ['Female', 'Male', 'Non-binary', 'Prefer not to say'],

    'delegation_capacities' => [
        'Head of Delegation',
        'Delegate',
        'Observer',
        'Speaker or Panellist',
        'Technical Expert',
        'Farmer Representative',
        'Private Sector Representative',
        'Development Partner',
        'Researcher or Academic',
        'Media',
        'Secretariat or Organiser',
        'Other',
    ],

    'dietary_requirements' => [
        'None',
        'Vegetarian',
        'Vegan',
        'Halal',
        'Kosher',
        'Gluten-free',
        'Other',
    ],

    'member_states' => [
        'Algeria', 'Angola', 'Benin', 'Botswana', 'Burkina Faso', 'Burundi', 'Cabo Verde',
        'Cameroon', 'Central African Republic', 'Chad', 'Comoros', 'Congo', "Cote d'Ivoire",
        'Democratic Republic of the Congo', 'Djibouti', 'Egypt', 'Equatorial Guinea', 'Eritrea',
        'Eswatini', 'Ethiopia', 'Gabon', 'Gambia', 'Ghana', 'Guinea', 'Guinea-Bissau', 'Kenya',
        'Lesotho', 'Liberia', 'Libya', 'Madagascar', 'Malawi', 'Mali', 'Mauritania', 'Mauritius',
        'Morocco', 'Mozambique', 'Namibia', 'Niger', 'Nigeria', 'Rwanda',
        'Sahrawi Arab Democratic Republic', 'Sao Tome and Principe', 'Senegal', 'Seychelles',
        'Sierra Leone', 'Somalia', 'South Africa', 'South Sudan', 'Sudan', 'Tanzania', 'Togo',
        'Tunisia', 'Uganda', 'Zambia', 'Zimbabwe', 'Not applicable',
    ],

    'countries' => [
        'Afghanistan', 'Albania', 'Algeria', 'Andorra', 'Angola', 'Antigua and Barbuda',
        'Argentina', 'Armenia', 'Australia', 'Austria', 'Azerbaijan', 'Bahamas', 'Bahrain',
        'Bangladesh', 'Barbados', 'Belarus', 'Belgium', 'Belize', 'Benin', 'Bhutan', 'Bolivia',
        'Bosnia and Herzegovina', 'Botswana', 'Brazil', 'Brunei', 'Bulgaria', 'Burkina Faso',
        'Burundi', 'Cabo Verde', 'Cambodia', 'Cameroon', 'Canada', 'Central African Republic',
        'Chad', 'Chile', 'China', 'Colombia', 'Comoros', 'Congo', 'Costa Rica', "Cote d'Ivoire",
        'Croatia', 'Cuba', 'Cyprus', 'Czechia', 'Democratic Republic of the Congo', 'Denmark',
        'Djibouti', 'Dominica', 'Dominican Republic', 'Ecuador', 'Egypt', 'El Salvador',
        'Equatorial Guinea', 'Eritrea', 'Estonia', 'Eswatini', 'Ethiopia', 'Fiji', 'Finland',
        'France', 'Gabon', 'Gambia', 'Georgia', 'Germany', 'Ghana', 'Greece', 'Grenada',
        'Guatemala', 'Guinea', 'Guinea-Bissau', 'Guyana', 'Haiti', 'Honduras', 'Hungary',
        'Iceland', 'India', 'Indonesia', 'Iran', 'Iraq', 'Ireland', 'Israel', 'Italy', 'Jamaica',
        'Japan', 'Jordan', 'Kazakhstan', 'Kenya', 'Kiribati', 'Kuwait', 'Kyrgyzstan', 'Laos',
        'Latvia', 'Lebanon', 'Lesotho', 'Liberia', 'Libya', 'Liechtenstein', 'Lithuania',
        'Luxembourg', 'Madagascar', 'Malawi', 'Malaysia', 'Maldives', 'Mali', 'Malta',
        'Marshall Islands', 'Mauritania', 'Mauritius', 'Mexico', 'Micronesia', 'Moldova',
        'Monaco', 'Mongolia', 'Montenegro', 'Morocco', 'Mozambique', 'Myanmar', 'Namibia',
        'Nauru', 'Nepal', 'Netherlands', 'New Zealand', 'Nicaragua', 'Niger', 'Nigeria',
        'North Korea', 'North Macedonia', 'Norway', 'Oman', 'Pakistan', 'Palau', 'Palestine',
        'Panama', 'Papua New Guinea', 'Paraguay', 'Peru', 'Philippines', 'Poland', 'Portugal',
        'Qatar', 'Romania', 'Russia', 'Rwanda', 'Sahrawi Arab Democratic Republic',
        'Saint Kitts and Nevis', 'Saint Lucia',
        'Saint Vincent and the Grenadines', 'Samoa', 'San Marino', 'Sao Tome and Principe',
        'Saudi Arabia', 'Senegal', 'Serbia', 'Seychelles', 'Sierra Leone', 'Singapore',
        'Slovakia', 'Slovenia', 'Solomon Islands', 'Somalia', 'South Africa', 'South Korea',
        'South Sudan', 'Spain', 'Sri Lanka', 'Sudan', 'Suriname', 'Sweden', 'Switzerland',
        'Syria', 'Tajikistan', 'Tanzania', 'Thailand', 'Timor-Leste', 'Togo', 'Tonga',
        'Trinidad and Tobago', 'Tunisia', 'Turkiye', 'Turkmenistan', 'Tuvalu', 'Uganda',
        'Ukraine', 'United Arab Emirates', 'United Kingdom', 'United States', 'Uruguay',
        'Uzbekistan', 'Vanuatu', 'Vatican City', 'Venezuela', 'Vietnam', 'Yemen', 'Zambia',
        'Zimbabwe',
    ],
];
