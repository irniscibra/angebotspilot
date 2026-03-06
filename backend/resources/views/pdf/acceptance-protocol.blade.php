<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: DejaVu Sans, sans-serif;
            font-size: 9pt;
            color: #2d3748;
            line-height: 1.6;
            padding:16px;
        }
        @page { margin: 25mm 25mm 32mm 25mm; }

        .footer {
            position: fixed;
            bottom: -22mm;
            left: 0; right: 0;
            height: 18mm;
            border-top: 2px solid {{ $company->primary_color ?? '#1E40AF' }};
            padding-top: 8px;
            font-size: 6.5pt;
            color: #a0aec0;
        }
        .footer-content { display: table; width: 100%; }
        .footer-col { display: table-cell; vertical-align: top; width: 33.33%; }
        .footer-col:nth-child(2) { text-align: center; }
        .footer-col:last-child { text-align: right; }
        .footer-label { font-weight: 700; color: #718096; }

        .header {
            padding: 5px 0 20px 0;
            margin-bottom: 30px;
            border-bottom: 3px solid {{ $company->primary_color ?? '#1E40AF' }};
        }
        .header-table { display: table; width: 100%; }
        .header-left { display: table-cell; vertical-align: bottom; width: 55%; }
        .header-right { display: table-cell; vertical-align: bottom; text-align: right; width: 45%; }
        .company-name {
            font-size: 20pt; font-weight: 700;
            color: {{ $company->primary_color ?? '#1E40AF' }};
            line-height: 1.2;
        }
        .company-subtitle { font-size: 8pt; color: #a0aec0; margin-top: 3px; }
        .header-contact { font-size: 7.5pt; color: #718096; line-height: 1.7; }

        .doc-title {
            font-size: 16pt; font-weight: 700;
            color: {{ $company->primary_color ?? '#1E40AF' }};
            margin-bottom: 5px;
        }
        .doc-subtitle {
            font-size: 9pt; color: #718096;
            margin-bottom: 30px;
        }

        .info-grid {
            display: table; width: 100%;
            margin-bottom: 30px;
            background: #f7fafc;
            padding: 16px;
        }
        .info-grid-left { display: table-cell; vertical-align: top; width: 50%; }
        .info-grid-right { display: table-cell; vertical-align: top; width: 50%; }
        .info-row {
            margin-bottom: 8px;
            font-size: 8.5pt;
        }
        .info-label {
            color: #a0aec0;
            font-size: 7pt;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 2px;
        }
        .info-value {
            color: #1a202c;
            font-weight: 500;
        }

        .section-title {
            font-size: 10pt; font-weight: 700;
            color: {{ $company->primary_color ?? '#1E40AF' }};
            border-bottom: 2px solid {{ $company->primary_color ?? '#1E40AF' }};
            padding-bottom: 6px;
            margin-top: 25px;
            margin-bottom: 14px;
        }

        .work-summary {
            font-size: 9pt;
            color: #4a5568;
            line-height: 1.8;
            margin-bottom: 20px;
        }

        .result-box {
            padding: 14px 18px;
            margin-bottom: 25px;
            font-size: 10pt;
            font-weight: 700;
        }
        .result-accepted {
            background: #f0fff4;
            border: 2px solid #48bb78;
            color: #276749;
        }
        .result-defects {
            background: #fffff0;
            border: 2px solid #ecc94b;
            color: #975a16;
        }
        .result-rejected {
            background: #fff5f5;
            border: 2px solid #fc8181;
            color: #9b2c2c;
        }

        .defects-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }
        .defects-table thead th {
            background: #f7fafc;
            border-bottom: 2px solid #e2e8f0;
            padding: 10px 12px;
            font-size: 7pt;
            font-weight: 700;
            color: #a0aec0;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            text-align: left;
        }
        .defects-table tbody td {
            padding: 10px 12px;
            border-bottom: 1px solid #edf2f7;
            font-size: 8.5pt;
            vertical-align: top;
        }
        .severity-badge {
            display: inline-block;
            font-size: 6pt;
            font-weight: 700;
            padding: 2px 8px;
            border-radius: 3px;
            text-transform: uppercase;
        }
        .severity-minor { background: #ebf8ff; color: #2b6cb0; }
        .severity-major { background: #fefcbf; color: #b7791f; }
        .severity-critical { background: #fed7d7; color: #9b2c2c; }

        .notes-section {
            margin-top: 15px;
            margin-bottom: 20px;
        }
        .notes-text {
            font-size: 9pt;
            color: #4a5568;
            line-height: 1.7;
            background: #f7fafc;
            padding: 12px 16px;
            min-height: 50px;
        }

        .signature-section {
            margin-top: 50px;
            display: table;
            width: 100%;
        }
        .signature-block {
            display: table-cell;
            width: 42%;
        }
        .signature-spacer {
            display: table-cell;
            width: 16%;
        }
        .signature-img {
            height: 60px;
            margin-bottom: 5px;
        }
        .signature-line {
            border-top: 1px solid #cbd5e0;
            padding-top: 8px;
            font-size: 7.5pt;
            color: #a0aec0;
            line-height: 1.5;
        }
        .signature-name {
            font-size: 9pt;
            font-weight: 600;
            color: #2d3748;
            margin-bottom: 2px;
        }
        .signature-date {
            font-size: 7.5pt;
            color: #a0aec0;
        }

        .legal-note {
            margin-top: 30px;
            padding-top: 15px;
            border-top: 1px solid #e2e8f0;
            font-size: 7pt;
            color: #a0aec0;
            line-height: 1.6;
        }
    </style>
</head>
<body>

    <!-- Footer -->
    <div class="footer">
        <div class="footer-content">
            <div class="footer-col">
                <span class="footer-label">{{ $company->name }}</span><br>
                {{ $company->address_street }}<br>
                {{ $company->address_zip }} {{ $company->address_city }}
            </div>
            <div class="footer-col">
                @if($company->phone)Tel: {{ $company->phone }}<br>@endif
                @if($company->email){{ $company->email }}<br>@endif
                @if($company->website){{ $company->website }}@endif
            </div>
            <div class="footer-col">
                @if($company->tax_id)USt-IdNr: {{ $company->tax_id }}<br>@endif
                @if($company->trade_register){{ $company->trade_register }}@endif
            </div>
        </div>
    </div>

    <!-- Header -->
    <div class="header">
        <div class="header-table">
            <div class="header-left">
                <div class="company-name">{{ $company->name }}</div>
                <div class="company-subtitle">Sanitär · Heizung · Klimatechnik</div>
            </div>
            <div class="header-right">
                <div class="header-contact">
                    {{ $company->address_street }}<br>
                    {{ $company->address_zip }} {{ $company->address_city }}<br>
                    @if($company->phone)Tel: {{ $company->phone }}<br>@endif
                    @if($company->email){{ $company->email }}@endif
                </div>
            </div>
        </div>
    </div>

    <!-- Titel -->
    <div class="doc-title">Abnahmeprotokoll</div>
    <div class="doc-subtitle">{{ $protocol->protocol_number }} · Angebot {{ $protocol->quote->quote_number }}</div>

    <!-- Projektdaten -->
    <div class="info-grid">
        <div class="info-grid-left">
            <div class="info-row">
                <div class="info-label">Projekt</div>
                <div class="info-value">{{ $protocol->project_title }}</div>
            </div>
            @if($protocol->project_address)
            <div class="info-row">
                <div class="info-label">Bauvorhaben / Adresse</div>
                <div class="info-value">{{ $protocol->project_address }}</div>
            </div>
            @endif
            <div class="info-row">
                <div class="info-label">Abnahmedatum</div>
                <div class="info-value">{{ $protocol->acceptance_date ? $protocol->acceptance_date->format('d.m.Y') : '_______________' }}</div>
            </div>
            @if($protocol->execution_start || $protocol->execution_end)
            <div class="info-row">
                <div class="info-label">Ausführungszeitraum</div>
                <div class="info-value">
                    {{ $protocol->execution_start ? $protocol->execution_start->format('d.m.Y') : '—' }}
                    bis
                    {{ $protocol->execution_end ? $protocol->execution_end->format('d.m.Y') : '—' }}
                </div>
            </div>
            @endif
        </div>
        <div class="info-grid-right">
            <div class="info-row">
                <div class="info-label">Auftragnehmer</div>
                <div class="info-value">{{ $protocol->contractor_name ?: $company->name }}</div>
            </div>
            <div class="info-row">
                <div class="info-label">Auftraggeber</div>
                <div class="info-value">{{ $protocol->client_name ?: '_______________' }}</div>
            </div>
            @if($protocol->client_representative)
            <div class="info-row">
                <div class="info-label">Vertreter Auftraggeber</div>
                <div class="info-value">{{ $protocol->client_representative }}</div>
            </div>
            @endif
        </div>
    </div>

    <!-- Zusammenfassung der Arbeiten -->
    <div class="section-title">Durchgeführte Arbeiten</div>
    <div class="work-summary">
        {!! nl2br(e($protocol->work_summary)) !!}
    </div>

    <!-- Ergebnis -->
    <div class="section-title">Ergebnis der Abnahme</div>
    @php
        $resultClass = match($protocol->result) {
            'accepted' => 'result-accepted',
            'accepted_with_defects' => 'result-defects',
            'rejected' => 'result-rejected',
            default => 'result-accepted',
        };
        $resultIcon = match($protocol->result) {
            'accepted' => '✓',
            'accepted_with_defects' => '⚠',
            'rejected' => '✗',
            default => '✓',
        };
    @endphp
    <div class="result-box {{ $resultClass }}">
        {{ $resultIcon }} {{ $protocol->result_label }}
    </div>

    <!-- Mängelliste -->
    @if(!empty($protocol->defects) && count($protocol->defects) > 0)
    <div class="section-title">Festgestellte Mängel</div>
    <table class="defects-table">
        <thead>
            <tr>
                <th style="width: 5%;">Nr.</th>
                <th style="width: 30%;">Mangel</th>
                <th style="width: 35%;">Beschreibung</th>
                <th style="width: 15%;">Schwere</th>
                <th style="width: 15%;">Frist</th>
            </tr>
        </thead>
        <tbody>
            @foreach($protocol->defects as $index => $defect)
            <tr>
                <td style="font-weight: 600; color: #a0aec0;">{{ str_pad($index + 1, 2, '0', STR_PAD_LEFT) }}</td>
                <td style="font-weight: 600; color: #1a202c;">{{ $defect['title'] ?? '' }}</td>
                <td>{{ $defect['description'] ?? '' }}</td>
                <td>
                    @php
                        $severity = $defect['severity'] ?? 'minor';
                        $severityLabel = match($severity) {
                            'minor' => 'Gering',
                            'major' => 'Wesentlich',
                            'critical' => 'Kritisch',
                            default => $severity,
                        };
                    @endphp
                    <span class="severity-badge severity-{{ $severity }}">{{ $severityLabel }}</span>
                </td>
                <td>{{ isset($defect['deadline']) ? \Carbon\Carbon::parse($defect['deadline'])->format('d.m.Y') : '—' }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>
    @endif

    <!-- Bemerkungen -->
    @if($protocol->notes)
    <div class="section-title">Bemerkungen</div>
    <div class="notes-section">
        <div class="notes-text">{!! nl2br(e($protocol->notes)) !!}</div>
    </div>
    @endif

    <!-- Vereinbarungen -->
    @if($protocol->agreements)
    <div class="section-title">Vereinbarungen</div>
    <div class="notes-section">
        <div class="notes-text">{!! nl2br(e($protocol->agreements)) !!}</div>
    </div>
    @endif

    <!-- Unterschriften -->
    <div class="section-title">Unterschriften</div>
    <div class="signature-section">
        <div class="signature-block">
            @if($protocol->signature_contractor)
                <img src="{{ $protocol->signature_contractor }}" class="signature-img" />
            @else
                <br><br><br>
            @endif
            <div class="signature-line">
                <div class="signature-name">{{ $protocol->contractor_name ?: $company->name }}</div>
                <div class="signature-date">
                    Auftragnehmer
                    @if($protocol->signed_contractor_at)
                        · {{ $protocol->signed_contractor_at->format('d.m.Y H:i') }}
                    @endif
                </div>
            </div>
        </div>
        <div class="signature-spacer"></div>
        <div class="signature-block">
            @if($protocol->signature_client)
                <img src="{{ $protocol->signature_client }}" class="signature-img" />
            @else
                <br><br><br>
            @endif
            <div class="signature-line">
                <div class="signature-name">{{ $protocol->client_name ?: 'Auftraggeber' }}</div>
                <div class="signature-date">
                    Auftraggeber
                    @if($protocol->signed_client_at)
                        · {{ $protocol->signed_client_at->format('d.m.Y H:i') }}
                    @endif
                </div>
            </div>
        </div>
    </div>

    <!-- Rechtshinweis -->
    <div class="legal-note">
        Dieses Protokoll dokumentiert die Abnahme der oben beschriebenen Leistungen. Mit der Unterschrift bestätigen
        beide Parteien die Richtigkeit der Angaben. Die Abnahme gilt als erfolgt im Sinne von § 640 BGB.
        Bekannte Mängel sind im Protokoll aufgeführt und deren Beseitigung vereinbart.
        Die Gewährleistungsfrist beginnt mit dem Datum der Abnahme.
    </div>

</body>
</html>