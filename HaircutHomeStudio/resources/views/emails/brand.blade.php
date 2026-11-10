@php
    $brand = '#5C293C';
    $bg = '#FAF7F5';
    $ink = '#3A2530';
    $soft = '#8C6B78';
    $title = $title ?? 'HairCut Home Studio';
    $greeting = $greeting ?? 'Hola,';
    $lines = $lines ?? [];
    $ctaUrl = $ctaUrl ?? null;
    $ctaLabel = $ctaLabel ?? null;
    $infoHtml = $infoHtml ?? null;
    $autoNote = $autoNote ?? 'Este es un correo automático. Por favor, no responder.';
@endphp
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $title }}</title>
</head>
<body style="margin:0;padding:24px 12px;background:{{ $bg }};font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',Roboto,Helvetica,Arial,sans-serif;color:{{ $ink }};">
    <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="max-width:560px;margin:0 auto;">
        <tr>
            <td style="background:{{ $brand }};color:#ffffff;border-radius:18px;padding:22px 24px;text-align:center;font-size:1.35rem;font-weight:700;letter-spacing:0.01em;">
                {{ $title }}
            </td>
        </tr>
        <tr>
            <td style="padding:28px 8px 8px;font-size:1.05rem;line-height:1.55;">
                <p style="margin:0 0 16px;"><strong>{{ $greeting }}</strong></p>
                @foreach($lines as $line)
                    <p style="margin:0 0 14px;color:{{ $ink }};">{{ $line }}</p>
                @endforeach
                @if($ctaUrl && $ctaLabel)
                    <p style="margin:24px 0 8px;text-align:center;">
                        <a href="{{ $ctaUrl }}" style="display:inline-block;background:{{ $brand }};color:#ffffff;text-decoration:none;font-weight:700;padding:12px 28px;border-radius:999px;">
                            {{ $ctaLabel }}
                        </a>
                    </p>
                @endif
                @if($infoHtml)
                    <p style="margin:22px 0 0;color:{{ $soft }};font-size:0.95rem;">{!! $infoHtml !!}</p>
                @endif
            </td>
        </tr>
        <tr>
            <td style="padding-top:22px;">
                <div style="background:#5a5a5a;color:#ffffff;border-radius:14px;padding:14px 18px;text-align:center;font-size:0.9rem;">
                    {{ $autoNote }}
                </div>
            </td>
        </tr>
    </table>
</body>
</html>
