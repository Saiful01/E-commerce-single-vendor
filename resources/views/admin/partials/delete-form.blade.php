<form id="del-{{ $key }}" method="POST" action="{{ $action }}" class="inline">
    @csrf
    @method('DELETE')
    <button type="button" onclick="confirmDelete('del-{{ $key }}', '{{ $message ?? 'You won\'t be able to revert this!' }}')"
            class="text-sm font-medium text-red-600 hover:text-red-800">Delete</button>
</form>
