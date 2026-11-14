@extends('layouts.auth')

@section('title', 'Create account')
@section('aside_title', 'Join and shop with less typing')
@section('heading', 'Create your account')
@section('subheading', 'It takes less than a minute. Use your mobile number so we can reach you about deliveries.')

@section('content')
    <form method="POST" action="{{ route('register') }}" class="space-y-4"
          x-data="{ busy: false, pw: '', get score() { let s = 0; if (this.pw.length >= 8) s++; if (/[A-Z]/.test(this.pw) && /[a-z]/.test(this.pw)) s++; if (/\d/.test(this.pw)) s++; if (/[^A-Za-z0-9]/.test(this.pw)) s++; return s } }"
          @submit="busy = true">
        @csrf
        <x-auth-field name="name" label="Full name" icon="bi-person" autocomplete="name" required autofocus />
        <div class="grid gap-4 sm:grid-cols-2">
            <x-auth-field name="phone" type="tel" label="Mobile number" icon="bi-phone" inputmode="tel" autocomplete="tel" placeholder="01XXXXXXXXX" required />
            <x-auth-field name="email" type="email" label="Email" icon="bi-envelope" autocomplete="email" required />
        </div>

        <div>
            <x-auth-field name="password" type="password" label="Password" icon="bi-lock" autocomplete="new-password" hint="Use at least 8 characters." x-model="pw" required />
            <div class="mt-2 flex gap-1" x-show="pw.length" x-cloak aria-hidden="true">
                <template x-for="i in 4"><span class="h-1 flex-1 rounded-full" :class="i <= score ? 'bg-brand' : 'bg-neutral-200'"></span></template>
            </div>
        </div>
        <x-auth-field name="password_confirmation" type="password" label="Confirm password" icon="bi-shield-lock" autocomplete="new-password" required />

        <button class="btn btn-primary w-full !py-3.5 text-base" :disabled="busy"><i class="bi bi-arrow-repeat animate-spin" x-show="busy" x-cloak></i> <span x-text="busy ? 'Creating account...' : 'Create account'"></span></button>
        <p class="text-center text-xs text-neutral-500">By creating an account you agree to our
            @if($terms = \App\Models\Page::urlFor('terms-and-conditions'))<a href="{{ $terms }}" target="_blank" class="underline">Terms</a>@else Terms @endif
            and
            @if($privacy = \App\Models\Page::urlFor('privacy-policy'))<a href="{{ $privacy }}" target="_blank" class="underline">Privacy Policy</a>@else Privacy Policy @endif.</p>
    </form>
@endsection

@section('footer')
    Already have an account? <a href="{{ route('login') }}" class="font-semibold text-brand hover:underline">Sign in</a>
@endsection
