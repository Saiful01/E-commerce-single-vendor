<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use App\Models\Slider;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class SliderController extends Controller
{
    public function index(Request $request): View
    {
        $q        = trim((string) $request->query('q', ''));
        $position = $request->query('position');
        $status   = $request->query('status'); // live | scheduled | expired | inactive

        if ($position && ! array_key_exists($position, Slider::POSITIONS)) {
            $position = null;
        }

        $sliders = Slider::query()
            ->when($position, fn ($query) => $query->forPosition($position))
            ->when($q !== '', fn ($query) => $query->where(fn ($w) => $w
                ->where('title', 'like', "%{$q}%")
                ->orWhere('subtitle', 'like', "%{$q}%")
                ->orWhere('btn_text', 'like', "%{$q}%")))
            ->when($status === 'inactive', fn ($query) => $query->where('status', false))
            ->when($status === 'live', fn ($query) => $query->active())
            ->when($status === 'scheduled', fn ($query) => $query->where('status', true)->where('starts_at', '>', now()))
            ->when($status === 'expired', fn ($query) => $query->where('status', true)->where('ends_at', '<', now()))
            ->orderBy('position')
            ->ordered()
            ->get();

        $counts = Slider::query()->selectRaw('position, count(*) as total')->groupBy('position')->pluck('total', 'position');

        return view('admin.sliders.index', [
            'sliders'  => $sliders,
            'counts'   => $counts,
            'total'    => $counts->sum(),
            'q'        => $q,
            'position' => $position,
            'status'   => $status,
        ]);
    }

    public function create(): View
    {
        return view('admin.sliders.create', $this->formData());
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate($this->rules($request));
        $disk = config('filesystems.default');

        $data = $this->normalize($request, $data);

        $data['image'] = $request->file('image')->store('sliders', $disk);

        if ($request->hasFile('mobile_image')) {
            $data['mobile_image'] = $request->file('mobile_image')->store('sliders', $disk);
        } else {
            unset($data['mobile_image']);
        }

        if (! isset($data['serial_order'])) {
            $data['serial_order'] = ((int) Slider::where('position', $data['position'])->max('serial_order')) + 1;
        }

        unset($data['remove_mobile_image']);

        Slider::create($data);

        return redirect()
            ->route('admin.sliders.index', ['position' => $data['position']])
            ->with('success', 'Banner created.');
    }

    public function edit(Slider $slider): View
    {
        return view('admin.sliders.edit', $this->formData() + ['slider' => $slider]);
    }

    public function update(Request $request, Slider $slider): RedirectResponse
    {
        $data = $request->validate($this->rules($request, $slider));
        $disk = config('filesystems.default');

        $data = $this->normalize($request, $data);

        if ($request->hasFile('image')) {
            $this->deleteFile($slider->image, $disk);
            $data['image'] = $request->file('image')->store('sliders', $disk);
        } else {
            unset($data['image']);
        }

        if ($request->hasFile('mobile_image')) {
            $this->deleteFile($slider->mobile_image, $disk);
            $data['mobile_image'] = $request->file('mobile_image')->store('sliders', $disk);
        } elseif ($request->boolean('remove_mobile_image')) {
            $this->deleteFile($slider->mobile_image, $disk);
            $data['mobile_image'] = null;
        } else {
            unset($data['mobile_image']);
        }

        if (! isset($data['serial_order'])) {
            unset($data['serial_order']);
        }

        unset($data['remove_mobile_image']);

        $slider->update($data);

        return redirect()
            ->route('admin.sliders.index', ['position' => $slider->position])
            ->with('success', 'Banner updated.');
    }

    public function destroy(Slider $slider): RedirectResponse
    {
        $disk = config('filesystems.default');

        $this->deleteFile($slider->image, $disk);
        $this->deleteFile($slider->mobile_image, $disk);
        $slider->delete();

        return back()->with('success', 'Banner deleted.');
    }

    /** PATCH /admin/sliders/{slider}/toggle */
    public function toggle(Slider $slider): JsonResponse
    {
        $slider->update(['status' => ! $slider->status]);

        return response()->json([
            'success' => true,
            'status'  => $slider->status,
            'state'   => $slider->live_state,
            'message' => $slider->status ? 'Banner enabled.' : 'Banner disabled.',
        ]);
    }

    /** POST /admin/sliders/reorder   body: { order: [id, id, ...] } */
    public function reorder(Request $request): JsonResponse
    {
        $request->validate([
            'order'   => 'required|array|min:1',
            'order.*' => 'integer|exists:sliders,id',
        ]);

        DB::transaction(function () use ($request) {
            foreach ($request->input('order') as $index => $id) {
                Slider::whereKey($id)->update(['serial_order' => $index + 1]);
            }
        });

        return response()->json(['success' => true, 'message' => 'Order saved.']);
    }

    /* ------------------------------ helpers ------------------------------ */

    private function formData(): array
    {
        return [
            'categories' => Category::orderBy('name')->get(['id', 'name']),
            'brands'     => Brand::orderBy('name')->get(['id', 'name']),
            'products'   => Product::orderBy('name')->limit(500)->get(['id', 'name', 'sku']),
        ];
    }

    private function rules(Request $request, ?Slider $slider = null): array
    {
        $hex      = 'regex:/^#[0-9A-Fa-f]{6}$/';
        $linkType = $request->input('link_type', 'none');

        $rules = [
            'title'               => 'required|string|max:255',
            'subtitle'            => 'nullable|string|max:255',
            'position'            => ['required', Rule::in(array_keys(Slider::POSITIONS))],
            'serial_order'        => 'nullable|integer|min:0|max:9999',

            'image'               => [$slider ? 'nullable' : 'required', 'image', 'mimes:jpeg,png,jpg,webp', 'max:4096'],
            'mobile_image'        => ['nullable', 'image', 'mimes:jpeg,png,jpg,webp', 'max:4096'],
            'remove_mobile_image' => 'nullable|boolean',

            'btn_text'            => 'nullable|string|max:50',
            'link_type'           => ['required', Rule::in(array_keys(Slider::LINK_TYPES))],
            'link_id'             => 'nullable|integer',
            'btn_link'            => 'nullable|string|max:255',

            'text_color'          => ['required', $hex],
            'text_align'          => ['required', Rule::in(['left', 'center', 'right'])],
            'overlay_opacity'     => 'required|integer|between:0,100',

            'starts_at'           => 'nullable|date',
            'ends_at'             => 'nullable|date|after:starts_at',
        ];

        match ($linkType) {
            'custom'   => $rules['btn_link'] = 'required|string|max:255',
            'category' => $rules['link_id']  = 'required|integer|exists:categories,id',
            'product'  => $rules['link_id']  = 'required|integer|exists:products,id',
            'brand'    => $rules['link_id']  = 'required|integer|exists:brands,id',
            default    => null,
        };

        return $rules;
    }

    /** Keeps only the link fields that match the chosen link type, and fills the booleans. */
    private function normalize(Request $request, array $data): array
    {
        $type = $data['link_type'];

        if ($type !== 'custom') {
            $data['btn_link'] = null;
        }
        if (! in_array($type, ['category', 'product', 'brand'], true)) {
            $data['link_id'] = null;
        }
        if ($type === 'none') {
            $data['btn_text'] = null;
        }

        $data['show_text']    = $request->boolean('show_text');
        $data['open_new_tab'] = $request->boolean('open_new_tab');
        $data['status']       = $request->boolean('status');
        $data['text_color']   = strtoupper($data['text_color']);

        return $data;
    }

    private function deleteFile(?string $path, string $disk): void
    {
        if (! $path) {
            return;
        }

        try {
            Storage::disk($disk)->delete($path);
        } catch (\Throwable $e) {
            report($e);
        }
    }
}
