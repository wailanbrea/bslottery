<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="utf-8">
<style>
    * { margin: 0; padding: 0; box-sizing: border-box; }
    body { font-family: DejaVu Sans, Arial, sans-serif; font-size: 9px; color: #1a1a2e; }
    .header { border-bottom: 2px solid #344767; padding-bottom: 8px; margin-bottom: 10px; }
    .header h1 { font-size: 14px; color: #344767; margin-bottom: 3px; }
    .meta { font-size: 8px; color: #666; }
    .meta span { margin-right: 14px; }
    table { width: 100%; border-collapse: collapse; margin-top: 6px; }
    thead tr th {
        background: #344767;
        color: #fff;
        padding: 5px 7px;
        text-align: left;
        font-size: 8px;
        font-weight: bold;
        white-space: nowrap;
    }
    tbody tr td { padding: 4px 7px; border-bottom: 1px solid #e9ecef; font-size: 8.5px; }
    tbody tr:nth-child(even) td { background: #f8f9fe; }
    .footer { margin-top: 10px; font-size: 7.5px; color: #888; border-top: 1px solid #e9ecef; padding-top: 5px; }
    .right { text-align: right; }
</style>
</head>
<body>
<div class="header">
    <h1>{{ $title }}</h1>
    <div class="meta">
        <span><strong>Empresa:</strong> {{ $companyName }}</span>
        <span><strong>Período:</strong> {{ $fromDate }} al {{ $toDate }}</span>
        @if($branchName)<span><strong>Sucursal:</strong> {{ $branchName }}</span>@endif
        <span><strong>Registros:</strong> {{ $totalRows }}</span>
        <span><strong>Generado:</strong> {{ now()->format('d/m/Y H:i') }}</span>
    </div>
</div>

<table>
    <thead>
        <tr>
            @foreach($headers as $header)
                <th>{{ $header }}</th>
            @endforeach
        </tr>
    </thead>
    <tbody>
        @forelse($rows as $row)
            <tr>
                @foreach($row as $cell)
                    <td>{{ $cell }}</td>
                @endforeach
            </tr>
        @empty
            <tr><td colspan="{{ count($headers) }}" style="text-align:center;padding:12px;color:#888;">Sin registros para los filtros aplicados.</td></tr>
        @endforelse
    </tbody>
</table>

<div class="footer">
    BSLotery &mdash; Reporte generado automáticamente. {{ now()->format('d/m/Y H:i:s') }}
</div>
</body>
</html>
