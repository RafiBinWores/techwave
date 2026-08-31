<?php

namespace App\Models;

use App\Services\UserAgentParser;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['session_id', 'user_id', 'url', 'referer', 'user_agent', 'ip_address', 'device', 'browser', 'os', 'created_at'])]
class Visit extends Model
{
    use HasFactory;

    public $timestamps = false;

    protected function casts(): array
    {
        return [
            'created_at' => 'datetime',
        ];
    }

    /**
     * The device type, falling back to parsing the stored user agent so
     * records created before the column existed are still classified.
     */
    protected function device(): Attribute
    {
        return Attribute::make(
            get: fn (?string $value) => $value ?? $this->parseFallback(
                fn ($parser, $agent) => $parser->device($agent)
            ),
        );
    }

    /**
     * The browser family, falling back to parsing the stored user agent.
     */
    protected function browser(): Attribute
    {
        return Attribute::make(
            get: fn (?string $value) => $value ?? $this->parseFallback(
                fn ($parser, $agent) => $parser->browser($agent)
            ),
        );
    }

    /**
     * The operating system family, falling back to parsing the stored user agent.
     */
    protected function os(): Attribute
    {
        return Attribute::make(
            get: fn (?string $value) => $value ?? $this->parseFallback(
                fn ($parser, $agent) => $parser->operatingSystem($agent)
            ),
        );
    }

    private function parse(): UserAgentParser
    {
        static $parser;

        return $parser ??= new UserAgentParser;
    }

    private function parseFallback(\Closure $callback): string
    {
        if ($this->user_agent === null || $this->user_agent === '') {
            return 'Unknown';
        }

        return $callback($this->parse(), $this->user_agent);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
