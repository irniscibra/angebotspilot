<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <style>
        /* ===== RESET & BASE ===== */
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: DejaVu Sans, sans-serif;
            font-size: 9pt;
            color: #2d3748;
            line-height: 1.6;
            padding:16px;
        }
        @page {
            margin: 25mm 25mm 32mm 25mm;
        }

        /* ===== FOOTER (fixed) ===== */
        .footer {
            position: fixed;
            bottom: -22mm;
            left: 0;
            right: 0;
            height: 18mm;
            border-top: 2px solid {{ $company->primary_color ?? '#1E40AF' }};
            padding-top: 8px;
            font-size: 6.5pt;
            color: #a0aec0;
            line-height: 1.5;
        }
        .footer-content { display: table; width: 100%; }
        .footer-col { display: table-cell; vertical-align: top; width: 33.33%; }
        .footer-col:nth-child(2) { text-align: center; }
        .footer-col:last-child { text-align: right; }
        .footer-label { font-weight: 700; color: #718096; }

        /* ===== HEADER ===== */
        .header {
            padding: 5px 0 20px 0;
            margin-bottom: 30px;
            border-bottom: 3px solid {{ $company->primary_color ?? '#1E40AF' }};
        }
        .header-table { display: table; width: 100%; }
        .header-left { display: table-cell; vertical-align: bottom; width: 55%; }
        .header-right { display: table-cell; vertical-align: bottom; text-align: right; width: 45%; }
        .company-name {
            font-size: 20pt;
            font-weight: 700;
            color: {{ $company->primary_color ?? '#1E40AF' }};
            letter-spacing: -0.3px;
            line-height: 1.2;
        }
        .company-subtitle {
            font-size: 8pt;
            color: #a0aec0;
            margin-top: 3px;
            letter-spacing: 0.3px;
        }
        .header-contact {
            font-size: 7.5pt;
            color: #718096;
            line-height: 1.7;
        }

        /* ===== ABSENDERZEILE ===== */
        .sender-line {
            font-size: 6.5pt;
            color: #a0aec0;
            border-bottom: 1px solid #e2e8f0;
            padding-bottom: 5px;
            margin-bottom: 25px;
            letter-spacing: 0.2px;
        }

        /* ===== ADRESS-BLOCK ===== */
        .address-section { display: table; width: 100%; margin-bottom: 40px; }
        .recipient-col { display: table-cell; vertical-align: top; width: 50%; padding-right: 15px; }
        .info-col { display: table-cell; vertical-align: top; width: 50%; }
        .recipient-name {
            font-weight: 700;
            font-size: 10.5pt;
            color: #1a202c;
            margin-bottom: 4px;
        }
        .recipient-address {
            font-size: 9pt;
            color: #4a5568;
            line-height: 1.7;
        }
        .info-table {
            margin-left: auto;
            border-collapse: collapse;
        }
        .info-table td {
            padding: 4px 0;
            font-size: 8.5pt;
            vertical-align: top;
        }
        .info-table td:first-child {
            color: #a0aec0;
            padding-right: 20px;
            white-space: nowrap;
        }
        .info-table td:last-child {
            font-weight: 600;
            color: #2d3748;
            text-align: right;
        }

        /* ===== TITEL & INTRO ===== */
        .quote-title {
            font-size: 14pt;
            font-weight: 700;
            color: {{ $company->primary_color ?? '#1E40AF' }};
            margin-bottom: 15px;
            padding-bottom: 10px;
            border-bottom: 1px solid #edf2f7;
        }
        .quote-intro {
            font-size: 9pt;
            color: #4a5568;
            margin-bottom: 35px;
            line-height: 1.8;
        }

        /* ===== GRUPPEN-HEADER ===== */
        .group-header {
            background: {{ $company->primary_color ?? '#1E40AF' }}0D;
            border-left: 4px solid {{ $company->primary_color ?? '#1E40AF' }};
            padding: 10px 16px;
            font-size: 9.5pt;
            font-weight: 700;
            color: {{ $company->primary_color ?? '#1E40AF' }};
            margin-top: 25px;
            margin-bottom: 0;
            letter-spacing: 0.2px;
        }

        /* ===== POSITIONEN TABELLE ===== */
        .items-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 10px;
        }
        .items-table thead th {
            background: #f7fafc;
            border-bottom: 2px solid #e2e8f0;
            padding: 10px 12px;
            font-size: 7pt;
            font-weight: 700;
            color: #a0aec0;
            text-transform: uppercase;
            letter-spacing: 0.8px;
        }
        .items-table thead th:first-child { text-align: left; width: 5%; }
        .items-table thead th:nth-child(2) { text-align: left; width: 43%; }
        .items-table thead th:nth-child(3) { text-align: right; width: 10%; }
        .items-table thead th:nth-child(4) { text-align: center; width: 12%; }
        .items-table thead th:nth-child(5) { text-align: right; width: 15%; }
        .items-table thead th:last-child { text-align: right; width: 15%; }

        .items-table tbody td {
            padding: 12px 12px;
            border-bottom: 1px solid #edf2f7;
            font-size: 8.5pt;
            vertical-align: top;
            color: #4a5568;
        }
        .items-table tbody tr:last-child td {
            border-bottom: none;
        }
        .item-title {
            font-weight: 600;
            color: #1a202c;
            font-size: 8.5pt;
        }
        .item-description {
            font-size: 7.5pt;
            color: #a0aec0;
            margin-top: 3px;
            line-height: 1.4;
        }
        .item-type {
            display: inline-block;
            font-size: 5.5pt;
            font-weight: 700;
            padding: 1px 6px;
            border-radius: 3px;
            margin-left: 6px;
            vertical-align: middle;
            letter-spacing: 0.3px;
            text-transform: uppercase;
        }
        .type-material { background: #ebf8ff; color: #2b6cb0; }
        .type-labor { background: #fefcbf; color: #b7791f; }
        .text-right { text-align: right; }
        .text-center { text-align: center; }

        /* ===== SUMMEN ===== */
        .totals-section {
            margin-top: 30px;
            display: table;
            width: 100%;
        }
        .totals-notes-col {
            display: table-cell;
            vertical-align: top;
            width: 48%;
            padding-right: 30px;
        }
        .totals-box-col {
            display: table-cell;
            vertical-align: top;
            width: 52%;
        }
        .totals-table {
            width: 100%;
            border-collapse: collapse;
            background: #f7fafc;
        }
        .totals-table td {
            padding: 8px 16px;
            font-size: 9pt;
        }
        .totals-table td:first-child { color: #718096; }
        .totals-table td:last-child { text-align: right; font-weight: 600; color: #2d3748; }
        .totals-table .subtotal-row td {
            border-bottom: 1px solid #e2e8f0;
            padding: 7px 16px;
            font-size: 8.5pt;
        }
        .totals-table .net-row td {
            padding-top: 10px;
        }
        .totals-table .vat-row td {
            color: #a0aec0;
            font-size: 8.5pt;
        }
        .totals-table .vat-row td:last-child {
            color: #718096;
            font-weight: 500;
        }
        .totals-table .total-row td {
            border-top: 3px solid {{ $company->primary_color ?? '#1E40AF' }};
            font-size: 13pt;
            font-weight: 700;
            padding-top: 12px;
            padding-bottom: 12px;
        }
        .totals-table .total-row td:last-child {
            color: {{ $company->primary_color ?? '#1E40AF' }};
        }

        /* ===== HINWEISE ===== */
        .notes-box {
            background: #fffff0;
            border: 1px solid #fefcbf;
            padding: 10px 14px;
        }
        .notes-title {
            font-size: 7.5pt;
            font-weight: 700;
            color: #b7791f;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 4px;
        }
        .notes-text {
            font-size: 7.5pt;
            color: #718096;
            line-height: 1.6;
        }

        /* ===== ZAHLUNGSBEDINGUNGEN ===== */
        .terms-section {
            margin-top: 35px;
            padding-top: 20px;
            border-top: 1px solid #e2e8f0;
        }
        .terms-label {
            font-size: 7.5pt;
            font-weight: 700;
            color: #a0aec0;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 6px;
        }
        .terms-text {
            font-size: 8pt;
            color: #718096;
            line-height: 1.7;
        }

        /* ===== SCHLUSSTEXT ===== */
        .closing-section {
            margin-top: 25px;
        }
        .closing-text {
            font-size: 9pt;
            color: #4a5568;
            line-height: 1.7;
        }

        /* ===== UNTERSCHRIFT ===== */
        .signature-section {
            margin-top: 50px;
            display: table;
            width: 100%;
        }
        .signature-block {
            display: table-cell;
            width: 42%;
            padding-top: 10px;
        }
        .signature-spacer {
            display: table-cell;
            width: 16%;
        }
        .signature-line {
            border-top: 1px solid #cbd5e0;
            padding-top: 8px;
            font-size: 7.5pt;
            color: #a0aec0;
            line-height: 1.5;
        }
    </style>
</head>
<body>

    <!-- ===== FOOTER ===== -->
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

    <!-- ===== HEADER ===== -->
    <div class="header">
        <div class="header-table">
            <div class="header-left">
                  @if($company->logo_path)
            <div style="margin-bottom: 10px;">
                <img src="{{ public_path('storage/' . $company->logo_path) }}" alt="Logo" style="max-height: 60px; max-width: 220px;">
            </div>
        @endif
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

    <!-- ===== ABSENDERZEILE ===== -->
    <div class="sender-line">
        {{ $company->name }} · {{ $company->address_street }} · {{ $company->address_zip }} {{ $company->address_city }}
    </div>

    <!-- ===== ADRESSE + INFO ===== -->
    <div class="address-section">
        <div class="recipient-col">
            @if($customer)
                @if($customer->type === 'business')
                    <div class="recipient-name">{{ $customer->company_name }}</div>
                    <div class="recipient-address">
                        @if($customer->contact_person){{ $customer->contact_person }}<br>@endif
                        @if($customer->address_street){{ $customer->address_street }}<br>@endif
                        @if($customer->address_zip){{ $customer->address_zip }} {{ $customer->address_city }}@endif
                    </div>
                @else
                    <div class="recipient-name">{{ $customer->first_name }} {{ $customer->last_name }}</div>
                    <div class="recipient-address">
                        @if($customer->address_street){{ $customer->address_street }}<br>@endif
                        @if($customer->address_zip){{ $customer->address_zip }} {{ $customer->address_city }}@endif
                    </div>
                @endif
            @else
                <div class="recipient-name">[Kundenname]</div>
                <div class="recipient-address">[Straße]<br>[PLZ Ort]</div>
            @endif
        </div>
        <div class="info-col">
            <table class="info-table">
                <tr><td>Angebots-Nr.:</td><td>{{ $quote->quote_number }}</td></tr>
                <tr><td>Datum:</td><td>{{ $quote->created_at->format('d.m.Y') }}</td></tr>
                <tr><td>Gültig bis:</td><td>{{ $quote->valid_until ? $quote->valid_until->format('d.m.Y') : '-' }}</td></tr>
                @if($quote->project_address)
                <tr><td>Bauvorhaben:</td><td>{{ $quote->project_address }}</td></tr>
                @endif
                <tr><td>Bearbeiter:</td><td>{{ $creator->name }}</td></tr>
            </table>
        </div>
    </div>

    <!-- ===== TITEL ===== -->
    <div class="quote-title">Angebot: {{ $quote->project_title }}</div>

    <!-- ===== EINLEITUNGSTEXT ===== -->
    <div class="quote-intro">
        @if($quote->header_text)
            {!! nl2br(e($quote->header_text)) !!}
        @else
            Sehr {{ $customer && $customer->type === 'business' ? 'geehrte Damen und Herren' : 'geehrte/r ' . ($customer ? ($customer->first_name . ' ' . $customer->last_name) : 'Kunde/Kundin') }},<br><br>
            vielen Dank für Ihre Anfrage. Gerne unterbreiten wir Ihnen folgendes Angebot für die beschriebenen Arbeiten.
            Alle Preise verstehen sich in Euro netto zzgl. der gesetzlichen Mehrwertsteuer.
        @endif
    </div>

    <!-- ===== POSITIONEN ===== -->
    @php $posNr = 1; @endphp

    @foreach($groupedItems as $groupName => $items)
        <div class="group-header">{{ $groupName ?: 'Positionen' }}</div>
        <table class="items-table">
            <thead>
                <tr>
                    <th>Pos.</th>
                    <th>Bezeichnung</th>
                    <th class="text-right">Menge</th>
                    <th class="text-center">Einheit</th>
                    <th class="text-right">Einzelpreis</th>
                    <th class="text-right">Gesamtpreis</th>
                </tr>
            </thead>
            <tbody>
                @foreach($items as $item)
                <tr>
                    <td style="color: #a0aec0; font-weight: 600;">{{ str_pad($posNr++, 2, '0', STR_PAD_LEFT) }}</td>
                    <td>
                        <div class="item-title">
                            {{ $item->title }}
                            <span class="item-type {{ $item->type === 'material' ? 'type-material' : 'type-labor' }}">
                                {{ $item->type === 'material' ? 'Material' : 'Arbeit' }}
                            </span>
                        </div>
                        @if($item->description)
                            <div class="item-description">{{ $item->description }}</div>
                        @endif
                    </td>
                    <td class="text-right" style="font-weight: 500;">{{ number_format($item->quantity, 2, ',', '.') }}</td>
                    <td class="text-center">{{ $item->unit }}</td>
                    <td class="text-right">{{ number_format($item->unit_price, 2, ',', '.') }}&nbsp;€</td>
                    <td class="text-right" style="font-weight: 600; color: #1a202c;">{{ number_format($item->total_price, 2, ',', '.') }}&nbsp;€</td>
                </tr>
                @endforeach
            </tbody>
        </table>
    @endforeach

    <!-- ===== SUMMEN ===== -->
    <div class="totals-section">
        <div class="totals-notes-col">
            @if($quote->ai_response && isset($quote->ai_response['notes']))
                <div class="notes-box">
                    <div class="notes-title">Hinweise zur Ausführung</div>
                    <div class="notes-text">{{ $quote->ai_response['notes'] }}</div>
                </div>
            @endif
        </div>
        <div class="totals-box-col">
            <table class="totals-table">
                <tr class="subtotal-row">
                    <td>Materialkosten</td>
                    <td>{{ number_format($quote->subtotal_materials, 2, ',', '.') }}&nbsp;€</td>
                </tr>
                <tr class="subtotal-row">
                    <td>Arbeitsleistung</td>
                    <td>{{ number_format($quote->subtotal_labor, 2, ',', '.') }}&nbsp;€</td>
                </tr>
                @if($quote->discount_percent > 0)
                <tr class="subtotal-row">
                    <td>Rabatt ({{ number_format($quote->discount_percent, 1, ',', '.') }}%)</td>
                    <td>-{{ number_format($quote->discount_amount, 2, ',', '.') }}&nbsp;€</td>
                </tr>
                @endif
                <tr class="net-row">
                    <td>Nettobetrag</td>
                    <td>{{ number_format($quote->subtotal_net, 2, ',', '.') }}&nbsp;€</td>
                </tr>
                <tr class="vat-row">
                    <td>MwSt. ({{ number_format($quote->vat_rate, 0) }}%)</td>
                    <td>{{ number_format($quote->vat_amount, 2, ',', '.') }}&nbsp;€</td>
                </tr>
                <tr class="total-row">
                    <td>Gesamtbetrag</td>
                    <td>{{ number_format($quote->total_gross, 2, ',', '.') }}&nbsp;€</td>
                </tr>
            </table>
        </div>
    </div>

    <!-- ===== ZAHLUNGSBEDINGUNGEN ===== -->
    <div class="terms-section">
        <div class="terms-label">Zahlungsbedingungen</div>
        <div class="terms-text">
            @if($quote->terms_text)
                {!! nl2br(e($quote->terms_text)) !!}
            @else
                Zahlbar innerhalb von 14 Tagen nach Rechnungsstellung ohne Abzug.
                Bei Aufträgen über 5.000&nbsp;€ netto wird eine Anzahlung in Höhe von 40% des Auftragswertes bei Auftragserteilung fällig.
                Dieses Angebot ist {{ $company->quote_validity_days ?? 30 }} Tage gültig.
            @endif
        </div>
    </div>

    <!-- ===== SCHLUSSTEXT ===== -->
    <div class="closing-section">
        <div class="closing-text">
            @if($quote->footer_text)
                {!! nl2br(e($quote->footer_text)) !!}
            @else
                Wir würden uns freuen, den Auftrag für Sie ausführen zu dürfen, und stehen für Rückfragen jederzeit gerne zur Verfügung.
            @endif
        </div>
    </div>

    <!-- ===== UNTERSCHRIFTEN ===== -->
    <div class="signature-section">
        <div class="signature-block">
            <br><br><br>
            <div class="signature-line">
                Ort, Datum &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp; {{ $creator->name }}<br>
                {{ $company->name }}
            </div>
        </div>
        <div class="signature-spacer"></div>
        <div class="signature-block">
            <br><br><br>
            <div class="signature-line">
                Ort, Datum &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp; Auftraggeber/in<br>
                Unterschrift zur Auftragserteilung
            </div>
        </div>
    </div>

</body>
</html>