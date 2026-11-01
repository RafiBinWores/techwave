@props(['rows' => []])

@if(!empty($rows))
    @php($columns = array_keys($rows[0]))
    <div class="overflow-x-auto"><table class="w-full min-w-[420px] table-fixed border-collapse text-xs [&_th]:border [&_th]:border-white/10 [&_th]:bg-white/6 [&_th]:p-3 [&_th]:text-left [&_th]:align-top [&_th]:font-semibold [&_th]:wrap-anywhere [&_th]:text-blue-100/60 [&_th]:capitalize [&_td]:border [&_td]:border-white/10 [&_td]:p-3 [&_td]:align-top [&_td]:wrap-anywhere [&_td]:text-blue-100/80"><thead><tr>@foreach($columns as $column)<th scope="col">{{ str_replace('_', ' ', $column) }}</th>@endforeach</tr></thead><tbody>
    @foreach(array_slice($rows, 0, 50) as $row)<tr>@foreach($columns as $column)<td>{{ is_bool($row[$column] ?? null) ? ($row[$column] ? 'Yes' : 'No') : (is_scalar($row[$column] ?? '') ? ($row[$column] ?? '') : json_encode($row[$column])) }}</td>@endforeach</tr>@endforeach
    </tbody></table></div>
@endif
