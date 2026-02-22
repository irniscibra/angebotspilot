<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
       body { 
    font-family: DejaVu Sans, sans-serif; 
    font-size: 9pt; 
    color: #1a1a2e; 
    line-height: 1.5; 
}
        @page { margin: 20mm 20mm 30mm 20mm; }

        .header { border-bottom: 3px solid {{ $company->primary_color ?? '#1E40AF' }}; padding: 15px; margin-bottom: 25px; }
        .header-top { display: table; width: 100%; margin-bottom: 8px; }
        .company-name { display: table-cell; vertical-align: top; width: 60%; }
        .company-name h1 { font-size: 18pt; font-weight: 700; color: {{ $company->primary_color ?? '#1E40AF' }}; letter-spacing: -0.5px; margin: 0; }
        .company-name .subtitle { font-size: 8pt; color: #6b7280; margin-top: 2px; }
        .company-contact { display: table-cell; vertical-align: top; text-align: right; font-size: 7.5pt; color: #6b7280; width: 40%; line-height: 1.6; }

        .sender-line { font-size: 6.5pt; color: #9ca3af; border-bottom: 1px solid #e5e7eb; padding-bottom: 3px; margin-bottom: 15px; }

        .address-block { display: table; width: 100%; margin-bottom: 30px; }
        .recipient { display: table-cell; vertical-align: top; width: 55%; padding:15px; }
        .recipient .name { font-weight: 700; font-size: 10pt; margin-bottom: 3px; }
        .quote-info { display: table-cell; vertical-align: top; text-align: right; width: 45%; }
        .quote-info-table { margin-left: auto; border-collapse: collapse; }
        .quote-info-table td { padding: 3px 0; font-size: 8.5pt; }
        .quote-info-table td:first-child { color: #6b7280; padding-right: 15px; }
        .quote-info-table td:last-child { font-weight: 600; text-align: right; }

        .quote-title { font-size: 14pt; font-weight: 700; color: {{ $company->primary_color ?? '#1E40AF' }}; margin-bottom: 8px; }
        .quote-intro { font-size: 9pt; color: #374151; margin-bottom: 25px; line-height: 1.7; }

        .group-header { background: {{ $company->primary_color ?? '#1E40AF' }}12; border-left: 3px solid {{ $company->primary_color ?? '#1E40AF' }}; padding: 6px 10px; font-size: 9pt; font-weight: 700; color: {{ $company->primary_color ?? '#1E40AF' }}; margin-top: 15px; margin-bottom: 0; }

        .items-table { width: 100%; border-collapse: collapse; margin-bottom: 5px; }
        .items-table thead th { background: #f8fafc; border-bottom: 2px solid #e2e8f0; padding: 7px 8px; font-size: 7.5pt; font-weight: 600; color: #64748b; text-transform: uppercase; letter-spacing: 0.5px; }
        .items-table thead th:first-child { text-align: left; width: 5%; }
        .items-table thead th:nth-child(2) { text-align: left; width: 43%; }
        .items-table thead th:nth-child(3) { text-align: right; width: 10%; }
        .items-table thead th:nth-child(4) { text-align: center; width: 12%; }
        .items-table thead th:nth-child(5) { text-align: right; width: 15%; }
        .items-table thead th:last-child { text-align: right; width: 15%; }
        .items-table tbody td { padding: 6px 8px; border-bottom: 1px solid #f1f5f9; font-size: 8.5pt; vertical-align: top; }
        .item-title { font-weight: 600; color: #1a1a2e; }
        .item-description { font-size: 7.5pt; color: #6b7280; margin-top: 1px; }
        .item-type { display: inline-block; font-size: 6pt; font-weight: 600; padding: 1px 5px; border-radius: 3px; margin-left: 5px; vertical-align: middle; }
        .type-material { background: #dbeafe; color: #1d4ed8; }
        .type-labor { background: #fef3c7; color: #b45309; }
        .text-right { text-align: right; }
        .text-center { text-align: center; }

        .totals-section { margin-top: 20px; display: table; width: 100%; }
        .totals-notes { display: table-cell; vertical-align: top; width: 50%; padding-right: 30px; }
        .totals-box { display: table-cell; vertical-align: top; width: 50%; }
        .totals-table { width: 100%; border-collapse: collapse; }
        .totals-table td { padding: 5px 10px; font-size: 9pt; }
        .totals-table td:first-child { color: #6b7280; }
        .totals-table td:last-child { text-align: right; font-weight: 600; }
        .totals-table .subtotal-row td { border-bottom: 1px solid #e2e8f0; }
        .totals-table .total-row td { border-top: 2px solid {{ $company->primary_color ?? '#1E40AF' }}; font-size: 12pt; font-weight: 700; padding-top: 10px; padding-bottom: 10px; }
        .totals-table .total-row td:last-child { color: {{ $company->primary_color ?? '#1E40AF' }}; }

        .notes-section { margin-top: 30px; padding-top: 15px; border-top: 1px solid #e5e7eb; }
        .notes-title { font-size: 8pt; font-weight: 700; color: #6b7280; text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 5px; }
        .notes-text { font-size: 8pt; color: #6b7280; line-height: 1.6; }
        .terms-section { margin-top: 20px; }
        .terms-text { font-size: 7.5pt; color: #9ca3af; line-height: 1.6; }

        .signature-section { margin-top: 40px; display: table; width: 100%; }
        .signature-block { display: table-cell; width: 45%; padding-top: 10px; }
        .signature-line { border-top: 1px solid #d1d5db; padding-top: 5px; font-size: 7.5pt; color: #9ca3af; }

        .footer { position: fixed; bottom: -20mm; left: 0; right: 0; height: 20mm; border-top: 1px solid #e5e7eb; padding-top: 5px; font-size: 6.5pt; color: #9ca3af; }
        .footer-content { display: table; width: 100%; }
        .footer-col { display: table-cell; vertical-align: top; width: 33.33%; }
        .footer-col:nth-child(2) { text-align: center; }
        .footer-col:last-child { text-align: right; }
        .footer-label { font-weight: 600; color: #6b7280; }
    </style>
</head>
<body>

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

    <div class="header">
        <div class="header-top">
            <div class="company-name">
                <h1>{{ $company->name }}</h1>
                <div class="subtitle">Sanitär · Heizung · Klimatechnik</div>
            </div>
            <div class="company-contact">
                {{ $company->address_street }}<br>
                {{ $company->address_zip }} {{ $company->address_city }}<br>
                @if($company->phone)Tel: {{ $company->phone }}<br>@endif
                @if($company->email){{ $company->email }}@endif
            </div>
        </div>
    </div>

    <div class="sender-line">
        {{ $company->name }} · {{ $company->address_street }} · {{ $company->address_zip }} {{ $company->address_city }}
    </div>

    <div class="address-block">
        <div class="recipient">
            @if($customer)
                @if($customer->type === 'business')
                    <div class="name">{{ $customer->company_name }}</div>
                    @if($customer->contact_person){{ $customer->contact_person }}<br>@endif
                @else
                    <div class="name">{{ $customer->first_name }} {{ $customer->last_name }}</div>
                @endif
                @if($customer->address_street){{ $customer->address_street }}<br>@endif
                @if($customer->address_zip){{ $customer->address_zip }} {{ $customer->address_city }}@endif
            @else
                <div class="name">[Kundenname]</div>
                [Straße]<br>
                [PLZ Ort]
            @endif
        </div>
        <div class="quote-info">
            <table class="quote-info-table">
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

    <div class="quote-title">Angebot: {{ $quote->project_title }}</div>

    <div class="quote-intro">
        @if($quote->header_text)
            {{ $quote->header_text }}
        @else
            Sehr {{ $customer && $customer->type === 'business' ? 'geehrte Damen und Herren' : 'geehrte/r ' . ($customer ? ($customer->first_name . ' ' . $customer->last_name) : 'Kunde/Kundin') }},<br><br>
            vielen Dank für Ihre Anfrage. Gerne unterbreiten wir Ihnen folgendes Angebot für die beschriebenen Arbeiten.
            Alle Preise verstehen sich in Euro netto zzgl. der gesetzlichen Mehrwertsteuer.
        @endif
    </div>

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
                    <td>{{ $posNr++ }}</td>
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
                    <td class="text-right">{{ number_format($item->quantity, 2, ',', '.') }}</td>
                    <td class="text-center">{{ $item->unit }}</td>
                    <td class="text-right">{{ number_format($item->unit_price, 2, ',', '.') }} €</td>
                    <td class="text-right">{{ number_format($item->total_price, 2, ',', '.') }} €</td>
                </tr>
                @endforeach
            </tbody>
        </table>
    @endforeach

    <div class="totals-section">
        <div class="totals-notes">
            @if($quote->ai_response && isset($quote->ai_response['notes']))
                <div class="notes-title">Hinweise zur Ausführung</div>
                <div class="notes-text">{{ $quote->ai_response['notes'] }}</div>
            @endif
        </div>
        <div class="totals-box">
            <table class="totals-table">
                <tr class="subtotal-row"><td>Materialkosten</td><td>{{ number_format($quote->subtotal_materials, 2, ',', '.') }} €</td></tr>
                <tr class="subtotal-row"><td>Arbeitsleistung</td><td>{{ number_format($quote->subtotal_labor, 2, ',', '.') }} €</td></tr>
                @if($quote->discount_percent > 0)
                <tr class="subtotal-row"><td>Rabatt ({{ number_format($quote->discount_percent, 1, ',', '.') }}%)</td><td>-{{ number_format($quote->discount_amount, 2, ',', '.') }} €</td></tr>
                @endif
                <tr><td>Nettobetrag</td><td>{{ number_format($quote->subtotal_net, 2, ',', '.') }} €</td></tr>
                <tr><td>MwSt. ({{ number_format($quote->vat_rate, 0) }}%)</td><td>{{ number_format($quote->vat_amount, 2, ',', '.') }} €</td></tr>
                <tr class="total-row"><td>Gesamtbetrag</td><td>{{ number_format($quote->total_gross, 2, ',', '.') }} €</td></tr>
            </table>
        </div>
    </div>

    <div class="notes-section">
        <div class="notes-title">Zahlungsbedingungen</div>
        <div class="terms-text">
            @if($quote->terms_text)
                {{ $quote->terms_text }}
            @else
                Zahlbar innerhalb von 14 Tagen nach Rechnungsstellung ohne Abzug.
                Bei Aufträgen über 5.000 € netto wird eine Anzahlung in Höhe von 40% des Auftragswertes bei Auftragserteilung fällig.
                Dieses Angebot ist {{ $company->quote_validity_days ?? 30 }} Tage gültig.
            @endif
        </div>
    </div>

    <div class="terms-section">
        <div class="terms-text">
            @if($quote->footer_text)
                {{ $quote->footer_text }}
            @else
                Wir würden uns freuen, den Auftrag für Sie ausführen zu dürfen, und stehen für Rückfragen gerne zur Verfügung.
            @endif
        </div>
    </div>

    <div class="signature-section">
        <div class="signature-block">
            <br><br><br>
            <div class="signature-line">
                Ort, Datum &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp; {{$creator->name  }}<br>
                {{ $company->name }}
            </div>
        </div>
        <div style="display: table-cell; width: 10%;"></div>
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