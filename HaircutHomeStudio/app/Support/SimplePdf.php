<?php

namespace App\Support;

/** Generador mínimo de PDF (A4, Helvetica). Coordenadas desde la esquina superior izquierda. */
class SimplePdf
{
    private array $pages = [];

    private string $current = '';

    private float $w = 595.28;

    private float $h = 841.89;

    private array $images = [];

    private array $imageNames = [];

    public function addPage(): void
    {
        if ($this->current !== '') {
            $this->pages[] = $this->current;
            $this->current = '';
        }
    }

    public function width(): float
    {
        return $this->w;
    }

    public function height(): float
    {
        return $this->h;
    }

    private function py(float $top): float
    {
        return $this->h - $top;
    }

    private function rgb(array $c): string
    {
        return sprintf('%.3f %.3f %.3f', $c[0] / 255, $c[1] / 255, $c[2] / 255);
    }

    public function rect(float $x, float $top, float $w, float $h, array $fill): void
    {
        $y = $this->py($top) - $h;
        $this->current .= $this->rgb($fill)." rg {$x} {$y} {$w} {$h} re f\n";
    }

    public function line(float $x1, float $top1, float $x2, float $top2, array $color, float $width = 0.6): void
    {
        $this->current .= $this->rgb($color)." RG {$width} w {$x1} ".$this->py($top1)." m {$x2} ".$this->py($top2)." l S\n";
    }

    public function text(float $x, float $top, float $size, string $text, bool $bold = false, array $color = [58, 37, 48]): void
    {
        $font = $bold ? 'F2' : 'F1';
        $y = $this->py($top) - $size;
        $t = $this->escape($this->latin($text));
        $this->current .= "BT /{$font} {$size} Tf ".$this->rgb($color)." rg 1 0 0 1 {$x} {$y} Tm ({$t}) Tj ET\n";
    }

    public function textWidth(string $text, float $size, bool $bold = false): float
    {
        return strlen($this->latin($text)) * $size * ($bold ? 0.52 : 0.48);
    }

    public function textCenter(float $cx, float $top, float $size, string $text, bool $bold = false, array $color = [58, 37, 48]): void
    {
        $this->text($cx - $this->textWidth($text, $size, $bold) / 2, $top, $size, $text, $bold, $color);
    }

    public function clip(string $text, float $size, float $max, bool $bold = false): string
    {
        if ($this->textWidth($text, $size, $bold) <= $max) {
            return $text;
        }
        while ($text !== '' && $this->textWidth($text.'...', $size, $bold) > $max) {
            $text = function_exists('mb_substr')
                ? mb_substr($text, 0, -1, 'UTF-8')
                : substr($text, 0, -1);
        }

        return $text.'...';
    }

    public function image(float $x, float $top, float $w, float $h, string $path, ?array $outsideRgb = null, bool $circular = false): bool
    {
        $key = $path.'|'.($outsideRgb ? implode(',', $outsideRgb) : '');
        if (! isset($this->imageNames[$key])) {
            $jpeg = $this->jpegDesdeArchivo($path, $outsideRgb);
            if ($jpeg === null) {
                return false;
            }
            $name = 'Im'.(count($this->images) + 1);
            $this->images[] = [
                'name' => $name,
                'data' => $jpeg['data'],
                'w' => $jpeg['w'],
                'h' => $jpeg['h'],
            ];
            $this->imageNames[$key] = $name;
        }
        $name = $this->imageNames[$key];
        $y = $this->py($top) - $h;
        if ($circular) {
            $cx = $x + $w / 2;
            $cy = $this->py($top) - $h / 2;
            $r = min($w, $h) / 2 - 0.35;
            $k = $r * 0.5522847498;
            $this->current .= "q\n";
            $this->current .= sprintf("%.2f %.2f m\n", $cx + $r, $cy);
            $this->current .= sprintf("%.2f %.2f %.2f %.2f %.2f %.2f c\n", $cx + $r, $cy + $k, $cx + $k, $cy + $r, $cx, $cy + $r);
            $this->current .= sprintf("%.2f %.2f %.2f %.2f %.2f %.2f c\n", $cx - $k, $cy + $r, $cx - $r, $cy + $k, $cx - $r, $cy);
            $this->current .= sprintf("%.2f %.2f %.2f %.2f %.2f %.2f c\n", $cx - $r, $cy - $k, $cx - $k, $cy - $r, $cx, $cy - $r);
            $this->current .= sprintf("%.2f %.2f %.2f %.2f %.2f %.2f c\n", $cx + $k, $cy - $r, $cx + $r, $cy - $k, $cx + $r, $cy);
            $this->current .= "W n\n";
        }
        $this->current .= sprintf(
            "q %.2f 0 0 %.2f %.2f %.2f cm /%s Do Q\n",
            $w,
            $h,
            $x,
            $y,
            $name
        );
        if ($circular) {
            $this->current .= "Q\n";
        }

        return true;
    }

    private function jpegDesdeArchivo(string $path, ?array $outsideRgb): ?array
    {
        if (! is_file($path)) {
            return null;
        }
        $raw = @file_get_contents($path);
        if ($raw === false || $raw === '') {
            return null;
        }
        if (strncmp($raw, "\xFF\xD8\xFF", 3) === 0) {
            $size = $this->jpegSize($raw);
            if ($size) {
                return ['data' => $raw, 'w' => $size[0], 'h' => $size[1]];
            }
        }
        if (! function_exists('imagecreatetruecolor')) {
            return null;
        }
        $src = @imagecreatefromstring($raw);
        if (! $src) {
            return null;
        }
        $sw = imagesx($src);
        $sh = imagesy($src);
        $max = 240;
        $nw = $sw;
        $nh = $sh;
        if ($sw > $max || $sh > $max) {
            $scale = $max / max($sw, $sh);
            $nw = max(1, (int) round($sw * $scale));
            $nh = max(1, (int) round($sh * $scale));
        }
        $dst = imagecreatetruecolor($nw, $nh);
        $bg = $outsideRgb ?: [255, 255, 255];
        $fill = imagecolorallocate($dst, (int) $bg[0], (int) $bg[1], (int) $bg[2]);
        imagefilledrectangle($dst, 0, 0, $nw, $nh, $fill);
        imagealphablending($dst, true);
        imagecopyresampled($dst, $src, 0, 0, 0, 0, $nw, $nh, $sw, $sh);
        imagedestroy($src);

        if ($outsideRgb) {
            $cx = ($nw - 1) / 2;
            $cy = ($nh - 1) / 2;
            $r = min($nw, $nh) / 2;
            $r2 = $r * $r;
            for ($yy = 0; $yy < $nh; $yy++) {
                for ($xx = 0; $xx < $nw; $xx++) {
                    $dx = $xx - $cx;
                    $dy = $yy - $cy;
                    if (($dx * $dx + $dy * $dy) > $r2) {
                        imagesetpixel($dst, $xx, $yy, $fill);
                    }
                }
            }
        }

        ob_start();
        imagejpeg($dst, null, 86);
        $data = ob_get_clean();
        imagedestroy($dst);
        if ($data === false || $data === '') {
            return null;
        }

        return ['data' => $data, 'w' => $nw, 'h' => $nh];
    }

    private function jpegSize(string $data): ?array
    {
        $len = strlen($data);
        $i = 2;
        while ($i + 8 < $len) {
            if (ord($data[$i]) !== 0xFF) {
                break;
            }
            $marker = ord($data[$i + 1]);
            if ($marker === 0xD9 || $marker === 0xDA) {
                break;
            }
            $seglen = unpack('n', substr($data, $i + 2, 2))[1];
            if (in_array($marker, [0xC0, 0xC1, 0xC2], true)) {
                $h = unpack('n', substr($data, $i + 5, 2))[1];
                $w = unpack('n', substr($data, $i + 7, 2))[1];

                return [$w, $h];
            }
            $i += 2 + $seglen;
        }

        return null;
    }

    private function latin(string $s): string
    {
        $out = @iconv('UTF-8', 'Windows-1252//TRANSLIT', $s);

        return $out === false ? $s : $out;
    }

    private function escape(string $s): string
    {
        return str_replace(['\\', '(', ')'], ['\\\\', '\\(', '\\)'], $s);
    }

    public function render(): string
    {
        if ($this->current !== '') {
            $this->pages[] = $this->current;
            $this->current = '';
        }
        if (! $this->pages) {
            $this->pages[] = '';
        }

        $objects = [];
        $objects[1] = '<< /Type /Catalog /Pages 2 0 R >>';
        $objects[3] = '<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica /Encoding /WinAnsiEncoding >>';
        $objects[4] = '<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica-Bold /Encoding /WinAnsiEncoding >>';

        $n = 5;
        $xobjParts = [];
        foreach ($this->images as $i => $im) {
            $len = strlen($im['data']);
            $objects[$n] = '<< /Type /XObject /Subtype /Image /Width '.(int) $im['w']
                .' /Height '.(int) $im['h']
                .' /ColorSpace /DeviceRGB /BitsPerComponent 8 /Filter /DCTDecode /Length '
                .$len." >>\nstream\n".$im['data'].'endstream';
            $xobjParts[] = '/'.$im['name'].' '.$n.' 0 R';
            $this->images[$i]['obj'] = $n;
            $n++;
        }
        $xobj = $xobjParts ? ' /XObject << '.implode(' ', $xobjParts).' >>' : '';

        $pageNums = [];
        foreach ($this->pages as $content) {
            $len = strlen($content);
            $objects[$n] = "<< /Length {$len} >>\nstream\n{$content}endstream";
            $contentId = $n;
            $n++;
            $objects[$n] = sprintf(
                '<< /Type /Page /Parent 2 0 R /MediaBox [0 0 %.2f %.2f] /Resources << /Font << /F1 3 0 R /F2 4 0 R >>%s >> /Contents %d 0 R >>',
                $this->w,
                $this->h,
                $xobj,
                $contentId
            );
            $pageNums[] = $n;
            $n++;
        }
        $kids = implode(' ', array_map(static function ($id) {
            return $id.' 0 R';
        }, $pageNums));
        $objects[2] = '<< /Type /Pages /Kids ['.$kids.'] /Count '.count($pageNums).' >>';
        ksort($objects);

        $body = "%PDF-1.4\n";
        $offsets = [0];
        foreach ($objects as $id => $obj) {
            $offsets[$id] = strlen($body);
            $body .= $id." 0 obj\n".$obj."\nendobj\n";
        }
        $max = max(array_keys($objects));
        $xref = strlen($body);
        $body .= "xref\n0 ".($max + 1)."\n";
        $body .= "0000000000 65535 f \n";
        for ($i = 1; $i <= $max; $i++) {
            $body .= sprintf("%010d 00000 n \n", $offsets[$i] ?? 0);
        }
        $body .= 'trailer << /Size '.($max + 1)." /Root 1 0 R >>\nstartxref\n{$xref}\n%%EOF";

        return $body;
    }
}
