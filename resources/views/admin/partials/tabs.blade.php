{{-- $tabs = [ ['label'=>'All','url'=>..., 'active'=>bool, 'count'=>int|null], ... ] --}}
<div class="flex flex-wrap gap-2 mb-4">
    @foreach($tabs as $tab)
        <a href="{{ $tab['url'] }}"
           class="px-3.5 py-1.5 rounded-full text-sm font-medium transition {{ $tab['active'] ? 'bg-indigo-600 text-white shadow' : 'bg-white text-gray-600 border border-gray-200 hover:bg-gray-50' }}">
            {{ $tab['label'] }}@if(isset($tab['count'])) <span class="opacity-70">({{ $tab['count'] }})</span>@endif
        </a>
    @endforeach
</div>
