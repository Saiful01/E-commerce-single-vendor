<?php

namespace App\View\Components;

use App\Models\Slider;
use Illuminate\View\Component;

class BannerSlot extends Component
{
    public array $meta;
    public $banners;

    public function __construct(public string $position)
    {
        $this->meta    = Slider::POSITIONS[$position] ?? ['type' => 'grid', 'size' => '800 × 400', 'mobile_size' => '800 × 400'];
        $this->banners = Slider::active()->forPosition($position)->ordered()->get();

        // Popup shows only the first active banner
        if ($this->meta['type'] === 'popup') {
            $this->banners = $this->banners->take(1);
        }
    }

    public function render()
    {
        return view('components.banner-slot');
    }
}
