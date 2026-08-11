<?php

namespace App\Models;

use App\Support\ServicePageLayout;
use Illuminate\Database\Eloquent\Model;

class ServicePageSetting extends Model
{
    protected $fillable = [
        'layout',
        'hero',
        'process',
        'why_choose_us',
        'cta',
    ];

    protected function casts(): array
    {
        return [
            'layout' => 'array',
            'hero' => 'array',
            'process' => 'array',
            'why_choose_us' => 'array',
            'cta' => 'array',
        ];
    }

    public static function defaults(): array
    {
        return [
            'layout' => [
                'layout_style' => 'bento',
            ],

            'hero' => [
                'enabled' => true,
                'badge' => 'Core Service Areas',
                'title' => 'Tailored services for',
                'highlighted_title' => 'growth, security, and stability',
                'description' => 'From foundational IT support to advanced enterprise protection, we design solutions that fit your business stage and operational needs.',
            ],

            'process' => [
                'enabled' => true,
                'badge' => 'How We Work',
                'title' => 'A refined process that turns',
                'highlighted_title' => 'complexity into clarity',
                'description' => 'We combine business understanding, technical precision, and structured execution to deliver solutions that feel smooth from planning to long-term support.',
                'steps' => [
                    ['icon' => 'document_scanner', 'title' => 'Assess & Understand', 'description' => 'We review your current setup, risks, pain points, business priorities, and growth objectives to understand the full picture before making decisions.'],
                    ['icon' => 'account_tree', 'title' => 'Plan & Architect', 'description' => 'We design the right structure, choose the best-fit technologies, and define the implementation flow for stability, usability, and scale.'],
                    ['icon' => 'verified', 'title' => 'Implement & Optimize', 'description' => 'Our team builds, configures, secures, and tests the solution carefully so it performs well in real business conditions.'],
                    ['icon' => 'support_agent', 'title' => 'Support & Evolve', 'description' => 'After launch, we stay involved with monitoring, improvements, maintenance, and strategic guidance so your systems stay reliable as you grow.'],
                ],
            ],

            'why_choose_us' => [
                'enabled' => true,
                'badge' => 'Why Choose Us',
                'title' => 'More than service delivery —',
                'highlighted_title' => 'we build dependable partnerships',
                'description' => 'We bring together business thinking, execution quality, and technical depth so your company gets solutions that are practical, secure, and built to last.',
                'items' => [
                    ['icon' => 'flag', 'title' => 'Business-Driven Strategy', 'description' => 'We shape every solution around business performance, operational clarity, and future growth.'],
                    ['icon' => 'verified_user', 'title' => 'Reliable Delivery', 'description' => 'Clear communication, disciplined execution, and dependable support from first discussion to final rollout.'],
                    ['icon' => 'shield_lock', 'title' => 'Security by Design', 'description' => 'Security is built into planning, access control, systems architecture, and support workflows.'],
                    ['icon' => 'trending_up', 'title' => 'Scalable Thinking', 'description' => 'Our work is designed to support where your business is today and where it needs to go next.'],
                ],
            ],

            'cta' => [
                'enabled' => true,
                'badge' => 'Contact Us',
                'title' => 'Let’s talk about your',
                'highlighted_title' => 'next IT solution',
                'description' => 'Tell us what you need — whether it is support, infrastructure, cybersecurity, web development, or a full business IT setup — and our team will get back to you.',
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

        // Numeric arrays must be replaced, not merged, so removed admin items
        // are never restored from the default indexes.
        $replaceLists = [
            'process' => ['steps'],
            'why_choose_us' => ['items'],
        ];

        foreach ($replaceLists as $section => $paths) {
            $savedSection = is_array($settings->{$section}) ? $settings->{$section} : [];

            foreach ($paths as $path) {
                if (array_key_exists($path, $savedSection) && is_array($savedSection[$path])) {
                    $result[$section][$path] = array_values($savedSection[$path]);
                }
            }
        }

        return $result;
    }

    public static function layoutStyle(): string
    {
        $layout = static::resolved()['layout'];

        return ServicePageLayout::normalize($layout['layout_style'] ?? 'bento');
    }
}
