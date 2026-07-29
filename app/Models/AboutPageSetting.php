<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AboutPageSetting extends Model
{
    protected $fillable = [
        'hero',
        'stats',
        'who_we_are',
        'mission_vision',
        'why_choose_us',
        'expertise',
        'timeline',
        'leadership',
        'experts',
    ];

    protected function casts(): array
    {
        return [
            'hero' => 'array',
            'stats' => 'array',
            'who_we_are' => 'array',
            'mission_vision' => 'array',
            'why_choose_us' => 'array',
            'expertise' => 'array',
            'timeline' => 'array',
            'leadership' => 'array',
            'experts' => 'array',
        ];
    }

    public static function defaults(): array
    {
        return [
            'hero' => [
                'enabled' => true,
                'badge' => 'About Our Company',
                'title' => 'We build smarter digital',
                'highlighted_title' => 'foundations for modern business',
                'description' => 'We combine technology, execution, and business understanding to help companies run more efficiently, stay protected, and grow with confidence.',
                'primary_button_text' => 'Learn More',
                'primary_button_url' => '#who-we-are',
                'secondary_button_text' => 'Contact Us',
                'secondary_button_url' => '/contact',
                'top_left_image' => 'https://images.unsplash.com/photo-1520607162513-77705c0f0d4a?auto=format&fit=crop&w=1000&q=80',
                'top_right_image' => 'https://images.unsplash.com/photo-1497366754035-f200968a6e72?auto=format&fit=crop&w=1000&q=80',
                'bottom_left_image' => 'https://images.unsplash.com/photo-1552664730-d307ca884978?auto=format&fit=crop&w=1000&q=80',
                'info_eyebrow' => 'Built Around',
                'info_title' => 'Trust, Performance, and Long-Term Value',
                'info_description' => 'We create systems that are practical, polished, and ready for growth.',
            ],

            'stats' => [
                ['value' => '10+', 'label' => 'Years of Experience'],
                ['value' => '250+', 'label' => 'Projects Delivered'],
                ['value' => '98%', 'label' => 'Client Satisfaction'],
                ['value' => '24/7', 'label' => 'Support Available'],
            ],

            'who_we_are' => [
                'enabled' => true,
                'badge' => 'Who We Are',
                'title' => 'A technology partner focused on practical outcomes',
                'image_url' => 'https://images.unsplash.com/photo-1521737711867-e3b97375f902?auto=format&fit=crop&w=1200&q=80',
                'paragraphs' => [
                    'We are a modern IT and digital solutions company helping businesses strengthen operations, reduce technical friction, and create more reliable digital systems.',
                    'Our work combines managed support, cybersecurity, cloud systems, infrastructure, web development, and business-focused technology planning into one practical service ecosystem.',
                    'We do not believe in unnecessary complexity. We believe in clean execution, clear communication, and solutions that truly support growth.',
                ],
            ],

            'mission_vision' => [
                'enabled' => true,
                'mission' => [
                    'title' => 'Our Mission',
                    'description' => 'To help businesses use technology with more clarity, confidence, and efficiency through secure, dependable, and thoughtfully designed digital solutions.',
                    'icon' => 'home',
                ],
                'vision' => [
                    'title' => 'Our Vision',
                    'description' => 'To become a trusted long-term technology partner for growing companies by delivering modern, scalable, and business-focused systems that truly create value.',
                    'icon' => 'visibility',
                ],
            ],

            'why_choose_us' => [
                'enabled' => true,
                'section_title' => 'Why businesses',
                'highlighted_title' => 'choose us',
                'subtitle' => 'We focus on what matters most — delivering reliable results that help your business grow.',
                'items' => [
                    ['title' => 'Business First', 'description' => 'We build around outcomes, not only technical tasks.'],
                    ['title' => 'Reliable Execution', 'description' => 'We focus on consistency, communication, and follow-through.'],
                    ['title' => 'Security Mindset', 'description' => 'Protection and resilience are part of everything we design.'],
                    ['title' => 'Scalable Systems', 'description' => "Our solutions are ready for growth, not just today's needs."],
                ],
            ],

            'expertise' => [
                'enabled' => true,
                'section_title' => 'Our areas of',
                'highlighted_title' => 'expertise',
                'subtitle' => 'Comprehensive technology services designed to keep your business running smoothly and securely.',
                'items' => [
                    [
                        'icon' => 'settings_suggest',
                        'eyebrow' => 'Managed Services',
                        'number' => '01',
                        'title' => 'Managed IT Support',
                        'description' => 'Structured technology management that keeps essential systems organized, maintained, and ready for daily business operations.',
                        'panel_title' => 'Operations overview',
                        'panel_subtitle' => 'Managed technology environment',
                        'panel_icon' => 'tune',
                        'features' => [
                            ['icon' => 'monitor_heart', 'title' => 'System monitoring', 'description' => 'Visibility across essential systems'],
                            ['icon' => 'inventory_2', 'title' => 'Asset management', 'description' => 'Organized devices and software'],
                            ['icon' => 'system_update_alt', 'title' => 'Updates & maintenance', 'description' => 'Planned upkeep and improvements'],
                        ],
                        'tags' => ['Endpoint management', 'Network care', 'Patch planning', 'Technology maintenance'],
                    ],
                    [
                        'icon' => 'shield_lock',
                        'eyebrow' => 'Protection Layer',
                        'number' => '02',
                        'title' => 'Cyber Security',
                        'description' => 'Practical safeguards for business systems, identities, access, and sensitive data.',
                        'panel_title' => 'Security posture',
                        'panel_subtitle' => 'Layered business protection',
                        'panel_icon' => 'verified_user',
                        'features' => [
                            ['icon' => 'check', 'title' => 'Threat prevention', 'description' => 'Reduce exposure to common risks'],
                            ['icon' => 'check', 'title' => 'Access protection', 'description' => 'Strengthen identity and permissions'],
                            ['icon' => 'check', 'title' => 'Operational resilience', 'description' => 'Prepare systems for disruption'],
                        ],
                        'tags' => ['Identity', 'Data', 'Network'],
                    ],
                    [
                        'icon' => 'code',
                        'eyebrow' => 'Digital Experience',
                        'number' => '03',
                        'title' => 'Website Development',
                        'description' => 'Modern responsive websites, portals, and business systems designed around real user journeys.',
                        'panel_title' => 'techwave.digital',
                        'panel_subtitle' => 'Responsive experience',
                        'panel_icon' => 'language',
                        'features' => [
                            ['icon' => 'view_quilt', 'title' => 'Clear structure', 'description' => 'Focused layouts and navigation'],
                            ['icon' => 'devices', 'title' => 'Responsive UI', 'description' => 'Consistent across device sizes'],
                            ['icon' => 'speed', 'title' => 'Performance', 'description' => 'Optimized frontend delivery'],
                        ],
                        'tags' => ['Laravel', 'Livewire', 'Modern UI'],
                    ],
                    [
                        'icon' => 'cloud',
                        'eyebrow' => 'Cloud Infrastructure',
                        'number' => '04',
                        'title' => 'Cloud Solutions',
                        'description' => 'Flexible infrastructure for hosting, communication, collaboration, continuity, and business growth.',
                        'panel_title' => 'Connected cloud ecosystem',
                        'panel_subtitle' => 'Built to scale with your operations',
                        'panel_icon' => 'hub',
                        'features' => [
                            ['icon' => 'dns', 'title' => 'Hosting', 'description' => 'Reliable application infrastructure'],
                            ['icon' => 'mail', 'title' => 'Business email', 'description' => 'Professional communication systems'],
                            ['icon' => 'cloud_sync', 'title' => 'Backup', 'description' => 'Protected business continuity'],
                            ['icon' => 'open_in_full', 'title' => 'Scaling', 'description' => 'Capacity that grows with demand'],
                        ],
                        'tags' => ['Hosting', 'Email', 'Backup', 'Scaling'],
                    ],
                ],
            ],

            'timeline' => [
                'enabled' => true,
                'section_title' => 'Our journey of',
                'highlighted_title' => 'steady growth',
                'subtitle' => 'From our founding to today, we have continuously evolved to better serve our clients.',
                'items' => [
                    ['year' => '2020', 'title' => 'The Foundation', 'description' => 'Started with a vision to provide smarter and more practical business IT support.'],
                    ['year' => '2022', 'title' => 'Expanded Capabilities', 'description' => 'Added web development, cloud systems, and stronger cybersecurity-focused services.'],
                    ['year' => '2024', 'title' => 'Broader Business Reach', 'description' => 'Worked with more diverse clients and built more complete digital service packages.'],
                    ['year' => 'Today', 'title' => 'Growth With Purpose', 'description' => 'Focused on long-term client partnerships, modern tools, and premium service quality.'],
                ],
            ],

            'leadership' => [
                'enabled' => true,
                'badge' => 'Leadership Message',
                'title' => 'A note from our leadership',
                'paragraphs' => [
                    'We believe technology should make business clearer, safer, and more efficient — not more complicated.',
                    'Our goal is to become the kind of partner clients can trust for both daily support and long-term growth decisions.',
                    'That means staying practical, communicating clearly, and delivering work that holds up in real-world business conditions.',
                ],
                'name' => 'Ahsan Rahman',
                'role' => 'Founder / Technology Lead',
                'image_url' => 'https://images.unsplash.com/photo-1500648767791-00dcc994a43e?auto=format&fit=crop&w=800&q=80',
            ],

            'experts' => [
                'enabled' => true,
                'section_title' => 'Meet our',
                'highlighted_title' => 'experts',
                'subtitle' => 'Meet the specialists behind our technology, security, cloud, and digital solutions.',
                'items' => [
                    ['name' => 'Ahsan Rahman', 'role' => 'IT Infrastructure Lead', 'image_url' => 'https://images.unsplash.com/photo-1500648767791-00dcc994a43e?auto=format&fit=crop&w=800&q=80'],
                    ['name' => 'Sarah Khan', 'role' => 'Cyber Security Specialist', 'image_url' => 'https://images.unsplash.com/photo-1494790108377-be9c29b29330?auto=format&fit=crop&w=800&q=80'],
                    ['name' => 'Nafis Ahmed', 'role' => 'Web Solutions Expert', 'image_url' => 'https://images.unsplash.com/photo-1506794778202-cad84cf45f1d?auto=format&fit=crop&w=800&q=80'],
                    ['name' => 'Rima Sultana', 'role' => 'Cloud Systems Consultant', 'image_url' => 'https://images.unsplash.com/photo-1488426862026-3ee34a7d66df?auto=format&fit=crop&w=800&q=80'],
                ],
            ],
        ];
    }

    public static function current(): self
    {
        return static::query()->firstOrCreate(
            ['id' => 1],
            static::defaults(),
        );
    }

    public static function resolved(): array
    {
        $defaults = static::defaults();
        $settings = static::query()->first();

        if (! $settings) {
            return $defaults;
        }

        $result = [];

        foreach ($defaults as $key => $section) {
            $saved = is_array($settings->{$key}) ? $settings->{$key} : [];
            $result[$key] = array_replace_recursive($section, $saved);
        }

        // Numeric arrays must be replaced, not recursively merged. Otherwise,
        // removed admin items can be restored from the default indexes.
        $replaceLists = [
            'stats' => [null],
            'who_we_are' => ['paragraphs'],
            'why_choose_us' => ['items'],
            'expertise' => ['items'],
            'timeline' => ['items'],
            'leadership' => ['paragraphs'],
            'experts' => ['items'],
        ];

        foreach ($replaceLists as $section => $paths) {
            $savedSection = is_array($settings->{$section}) ? $settings->{$section} : [];

            foreach ($paths as $path) {
                if ($path === null) {
                    if ($savedSection !== []) {
                        $result[$section] = array_values($savedSection);
                    }

                    continue;
                }

                if (array_key_exists($path, $savedSection) && is_array($savedSection[$path])) {
                    $result[$section][$path] = array_values($savedSection[$path]);
                }
            }
        }

        // Merge every expertise card with its latest defaults. This keeps older
        // saved records compatible when new editable fields are introduced.
        $savedExpertise = is_array($settings->expertise) ? $settings->expertise : [];
        $savedExpertiseItems = is_array($savedExpertise['items'] ?? null)
            ? array_values($savedExpertise['items'])
            : [];

        if ($savedExpertiseItems !== []) {
            $defaultExpertiseItems = $defaults['expertise']['items'];
            $result['expertise']['items'] = [];

            foreach ($savedExpertiseItems as $index => $savedItem) {
                $fallback = $defaultExpertiseItems[$index]
                    ?? [
                        'icon' => 'settings_suggest',
                        'eyebrow' => 'Technology Service',
                        'number' => str_pad((string) ($index + 1), 2, '0', STR_PAD_LEFT),
                        'title' => '',
                        'description' => '',
                        'panel_title' => '',
                        'panel_subtitle' => '',
                        'panel_icon' => 'widgets',
                        'features' => [],
                        'tags' => [],
                    ];

                $mergedItem = array_replace_recursive($fallback, is_array($savedItem) ? $savedItem : []);

                if (isset($savedItem['features']) && is_array($savedItem['features'])) {
                    $mergedItem['features'] = array_values($savedItem['features']);
                }

                if (isset($savedItem['tags']) && is_array($savedItem['tags'])) {
                    $mergedItem['tags'] = array_values($savedItem['tags']);
                }

                $result['expertise']['items'][] = $mergedItem;
            }
        }

        return $result;
    }
}
