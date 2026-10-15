<!DOCTYPE html>
<html>
<head>
<meta charset="utf-8">
<style>
    @page { margin: 0; }
    * { margin: 0; padding: 0; box-sizing: border-box; }
    body { font-family: 'DejaVu Sans', sans-serif; color: #17263f; }
    .sheet {
        position: relative;
        width: 297mm; height: 210mm;
        background: #faf7ef; /* ivory */
        padding: 14mm 16mm 12mm;
        overflow: hidden;
    }
    .frame {
        position: absolute; inset: 7mm;
        border: 1.2mm solid #08355c; /* navy */
    }
    .frame-inner {
        position: absolute; inset: 8.5mm;
        border: 0.35mm solid #c9a24b; /* gold */
    }
    .band {
        position: absolute; top: 0; left: 0; right: 0;
        height: 10mm; background: #08355c;
    }
    .band-gold {
        position: absolute; top: 10mm; left: 0; right: 0;
        height: 0.8mm; background: #c9a24b;
    }
    .content { position: relative; text-align: center; padding-top: 12mm; }
    .logo { height: 17mm; margin-bottom: 2mm; }
    .org {
        font-size: 12.5pt; letter-spacing: 3.2pt; text-transform: uppercase;
        color: #08355c; font-weight: bold; margin-top: 1mm;
    }
    .org-sub { font-size: 7.5pt; letter-spacing: 2pt; color: #5d6a80; text-transform: uppercase; margin-top: 1.5mm; }
    .rule { width: 55mm; height: 0.35mm; background: #c9a24b; margin: 7mm auto; }
    .kicker { font-size: 9pt; letter-spacing: 4pt; text-transform: uppercase; color: #8a6d25; }
    .title { font-family: 'DejaVu Serif', serif; font-size: 32pt; color: #08355c; margin-top: 3mm; font-weight: bold; }
    .given { font-size: 10pt; color: #5d6a80; margin-top: 9mm; }
    .name {
        font-family: 'DejaVu Serif', serif; font-size: 25pt; font-style: italic;
        color: #17263f; margin-top: 2mm; padding-bottom: 2mm;
        border-bottom: 0.3mm solid #c9a24b; display: inline-block; padding-left: 18mm; padding-right: 18mm;
    }
    .course { font-size: 13pt; color: #08355c; margin-top: 7mm; font-weight: bold; }
    .meta { margin-top: 12mm; font-size: 8pt; color: #5d6a80; }
    .meta strong { color: #17263f; }
    .footer {
        position: absolute; top: 178mm; left: 16mm; right: 16mm;
        font-size: 7.5pt; color: #5d6a80;
    }
    .foot-table { width: 100%; border-collapse: collapse; }
    .foot-table td { vertical-align: bottom; padding: 0; }
    .sig-cell { width: 60%; text-align: center; }
    .sig-line { display: inline-block; width: 60mm; border-top: 0.3mm solid #08355c; padding-top: 1.5mm; margin-top: 6mm; }
    .sig-name { font-weight: bold; color: #17263f; font-size: 8.5pt; }
    .sig-title { color: #5d6a80; font-size: 7pt; margin-top: 0.5mm; }
    .qr-cell { width: 40%; text-align: right; }
    .qr-cell img { width: 18mm; height: 18mm; }
    .qr-cap { font-size: 6.5pt; color: #5d6a80; margin-top: 1mm; }
    .certno { position: absolute; top: 170mm; left: 0; right: 0; text-align: center; font-size: 8pt; letter-spacing: 1.5pt; color: #8a6d25; }
</style>
</head>
<body>
<div class="sheet">
    <div class="band"></div>
    <div class="band-gold"></div>
    <div class="frame"></div>
    <div class="frame-inner"></div>

    <div class="content">
        <img class="logo" src="{{ $logoDataUri }}" alt="EuroAfrica Media Academy">
        <div class="org">EuroAfrica Media Academy</div>
        <div class="org-sub">Professional Broadcast &amp; Media Training</div>
        <div class="rule"></div>
        <div class="kicker">This is to certify that</div>
        <div class="title">Certificate of Completion</div>
        <div class="given">is proudly presented to</div>
        <div class="name">{{ $cert->recipient_name }}</div>
        <div class="given" style="margin-top:6mm;">for successful completion of</div>
        <div class="course">{{ $cert->course_title }}</div>
        <div class="meta">
            Completed <strong>{{ $cert->completed_at->format('j F Y') }}</strong>
            &nbsp;·&nbsp; Issued <strong>{{ $cert->issued_at->format('j F Y') }}</strong>
        </div>
    </div>

    <div class="certno">Certificate No. {{ $cert->number }}</div>

    <div class="footer">
        <table class="foot-table"><tr>
            <td class="sig-cell">
                <div class="sig-line">
                    <div class="sig-name">{{ $cert->signatory_name ?? 'Academic Board' }}</div>
                    <div class="sig-title">{{ $cert->signatory_title ?? 'EuroAfrica Media Academy' }}</div>
                </div>
            </td>
            <td class="qr-cell">
                <img src="{{ $qrDataUri }}" alt="Verify">
                <div class="qr-cap">Scan to verify · {{ str_replace('https://', '', $cert->verificationUrl()) }}</div>
            </td>
        </tr></table>
    </div>
</div>
</body>
</html>
