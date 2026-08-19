<?php

namespace App\Support;

use Dompdf\Dompdf;
use Dompdf\Options;

class PdfFonts
{
    private const FAMILY = 'Hind Siliguri';

    /**
     * Maps the dompdf style key (font-weight / style) to the source TTF file.
     *
     * @var array<string, string>
     */
    private const WEIGHTS = [
        '300' => 'HindSiliguri-Light.ttf',
        'normal' => 'HindSiliguri-Regular.ttf',
        '500' => 'HindSiliguri-Medium.ttf',
        '600' => 'HindSiliguri-SemiBold.ttf',
        'bold' => 'HindSiliguri-Bold.ttf',
        '800' => 'HindSiliguri-Bold.ttf',
        '900' => 'HindSiliguri-Bold.ttf',
    ];

    /**
     * Registers the Hind Siliguri font with Dompdf so the taka symbol (U+09F3)
     * and Bengali numerals render correctly in generated PDFs.
     */
    public static function register(): void
    {
        $fontsDir = storage_path('fonts');

        if (static::cacheIsComplete($fontsDir)) {
            return;
        }

        $fontMetrics = (new Dompdf(new Options([
            'fontDir' => $fontsDir,
            'fontCache' => $fontsDir,
            'tempDir' => sys_get_temp_dir(),
            'chroot' => realpath(base_path()),
        ])))->getFontMetrics();

        $entries = [];

        foreach (self::WEIGHTS as $weight => $file) {
            $source = $fontsDir.'/'.$file;

            if (! is_file($source)) {
                continue;
            }

            if ($fontMetrics->registerFont([
                'family' => self::FAMILY,
                'weight' => $weight,
                'style' => 'normal',
            ], $source)) {
                $entries[mb_strtolower(self::FAMILY, 'UTF-8')][$weight] = $fontsDir.'/hind_siliguri_'.$weight.'_'.md5($source);
            }
        }

        if ($entries !== []) {
            file_put_contents($fontsDir.'/installed-fonts.json', json_encode($entries, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
        }
    }

    private static function cacheIsComplete(string $fontsDir): bool
    {
        $cacheFile = $fontsDir.'/installed-fonts.json';

        if (! is_file($cacheFile)) {
            return false;
        }

        $existing = json_decode((string) file_get_contents($cacheFile), true);

        if (! is_array($existing) || ! isset($existing['hind siliguri'])) {
            return false;
        }

        foreach (self::WEIGHTS as $weight => $file) {
            $path = $existing['hind siliguri'][$weight] ?? null;

            if (! is_string($path) || ! is_file($path.'.ttf')) {
                return false;
            }
        }

        return true;
    }
}
