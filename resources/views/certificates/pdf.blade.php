<!DOCTYPE html>
<html>
<head>
<meta charset="utf-8">
<style>
    /*
     * A4 landscape = 297mm x 210mm. Every major block is absolutely
     * positioned with explicit top/left/width so nothing can overflow the
     * printable area or collide. dompdf-safe: no flexbox/grid/floats —
     * block elements + one layout table for the footer.
     */
    @page { size: a4 landscape; margin: 0; }
    * { margin: 0; padding: 0; }
    body { font-family: 'DejaVu Sans', sans-serif; color: #17263f; }
    .page { position: relative; width: 297mm; height: 210mm; }

    /* chrome */
    .band { position: absolute; top: 0; left: 0; width: 297mm; height: 9mm; background: #08355c; }
    .band-gold { position: absolute; top: 9mm; left: 0; width: 297mm; height: 0.9mm; background: #c9a24b; }
    .frame { position: absolute; top: 7mm; left: 7mm; width: 283mm; height: 196mm; border: 1mm solid #08355c; }
    .frame-inner { position: absolute; top: 9mm; left: 9mm; width: 279mm; height: 192mm; border: 0.3mm solid #c9a24b; }
    .ground { position: absolute; top: 10mm; left: 10mm; width: 277mm; height: 190mm; background: #faf7ef; }

    /* header — centred column */
    .logo { position: absolute; top: 16mm; left: 124mm; height: 16mm; }
    .org {
        position: absolute; top: 35mm; left: 40mm; width: 217mm; text-align: center;
        font-size: 12pt; letter-spacing: 3pt; text-transform: uppercase;
        color: #08355c; font-weight: bold;
    }
    .org-sub {
        position: absolute; top: 42mm; left: 40mm; width: 217mm; text-align: center;
        font-size: 7.5pt; letter-spacing: 2pt; color: #5d6a80; text-transform: uppercase;
    }
    .rule { position: absolute; top: 50mm; left: 121mm; width: 55mm; height: 0.4mm; background: #c9a24b; }

    /* body copy — each row its own y so nothing can overlap */
    .kicker {
        position: absolute; top: 58mm; left: 40mm; width: 217mm; text-align: center;
        font-size: 9pt; letter-spacing: 4pt; text-transform: uppercase; color: #8a6d25;
    }
    .title {
        position: absolute; top: 64mm; left: 40mm; width: 217mm; text-align: center;
        font-family: 'DejaVu Serif', serif; font-size: 31pt; color: #08355c; font-weight: bold;
    }
    .given1 { position: absolute; top: 82mm; left: 40mm; width: 217mm; text-align: center; font-size: 10pt; color: #5d6a80; }
    .name {
        position: absolute; top: 89mm; left: 45mm; width: 207mm; text-align: center;
        font-family: 'DejaVu Serif', serif; font-size: 24pt; font-style: italic; color: #17263f;
        padding-bottom: 1.5mm; border-bottom: 0.3mm solid #c9a24b;
        /* long names shrink: dompdf clamps font via smaller face */
    }
    .name.long { font-size: 18pt; }
    .name.xlong { font-size: 14pt; }
    .given2 { position: absolute; top: 110mm; left: 40mm; width: 217mm; text-align: center; font-size: 10pt; color: #5d6a80; }
    .course {
        position: absolute; top: 117mm; left: 45mm; width: 207mm; text-align: center;
        font-size: 13pt; color: #08355c; font-weight: bold;
    }
    .course.long { font-size: 11pt; }

    /* dates + number — three separate fields, no shared line */
    .dates { position: absolute; top: 140mm; left: 40mm; width: 217mm; text-align: center; font-size: 8.5pt; color: #5d6a80; }
    .dates strong { color: #17263f; }
    .certno {
        position: absolute; top: 150mm; left: 40mm; width: 217mm; text-align: center;
        font-size: 8pt; letter-spacing: 1.5pt; color: #8a6d25;
    }

    /* footer strip — fixed table inside a hard-bounded cell */
    .footer { position: absolute; top: 165mm; left: 16mm; width: 265mm; }
    .foot { width: 265mm; border-collapse: collapse; }
    .foot td { vertical-align: bottom; padding: 0; }
    .sigcell { width: 88mm; text-align: center; }
    .sig-line { display: inline-block; width: 62mm; border-top: 0.3mm solid #08355c; padding-top: 1.5mm; }
    .sig-name { font-weight: bold; color: #17263f; font-size: 8.5pt; }
    .sig-title { color: #5d6a80; font-size: 7pt; margin-top: 0.5mm; }
    .midcell { width: 89mm; text-align: center; }
    .midcell .vfy { font-size: 7pt; color: #5d6a80; }
    .midcell .vfy b { color: #08355c; }
    .qrcell { width: 88mm; text-align: center; }
    .qrcell img { width: 19mm; height: 19mm; }
    .qrcap { font-size: 6.5pt; color: #5d6a80; margin-top: 1mm; }
</style>
</head>
<body>
<div class="page">
    <div class="band"></div>
    <div class="band-gold"></div>
    <div class="frame"></div>
    <div class="frame-inner"></div>
    <div class="ground"></div>

    <img class="logo" src="{{ $logoDataUri }}" alt="EuroAfrica Media Academy">

    <div class="org">EuroAfrica Media Academy</div>
    <div class="org-sub">Professional Broadcast &amp; Media Training</div>
    <div class="rule"></div>

    <div class="kicker">This is to certify that</div>
    <div class="title">Certificate of Completion</div>
    <div class="given1">is proudly presented to</div>
    @php($nameLen = mb_strlen($cert->recipient_name))
    <div class="name {{ $nameLen > 45 ? 'xlong' : ($nameLen > 28 ? 'long' : '') }}">{{ $cert->recipient_name }}</div>
    <div class="given2">for successful completion of</div>
    <div class="course {{ mb_strlen($cert->course_title) > 60 ? 'long' : '' }}">{{ $cert->course_title }}</div>

    <div class="dates">
        Completed <strong>{{ $cert->completed_at->format('j F Y') }}</strong>
        &nbsp;&nbsp;·&nbsp;&nbsp; Issued <strong>{{ $cert->issued_at->format('j F Y') }}</strong>
    </div>
    <div class="certno">Certificate No. {{ $cert->number }}</div>

    <div class="footer">
        <table class="foot"><tr>
            <td class="sigcell">
                @if ($cert->signatory_name)
                    <div class="sig-line">
                        <div class="sig-name">{{ $cert->signatory_name }}</div>
                        <div class="sig-title">{{ $cert->signatory_title ?? 'EuroAfrica Media Academy' }}</div>
                    </div>
                @endif
            </td>
            <td class="midcell">
                <div class="vfy">Verify this certificate at<br><b>{{ parse_url(config('app.academy_url'), PHP_URL_HOST) }}/verify/{{ $cert->public_ref }}</b></div>
            </td>
            <td class="qrcell">
                <img src="{{ $qrDataUri }}" alt="Verify">
                <div class="qrcap">Scan to verify</div>
            </td>
        </tr></table>
    </div>
</div>
</body>
</html>
