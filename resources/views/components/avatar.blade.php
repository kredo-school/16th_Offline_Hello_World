@props([
 'url' => null,
 'name' => 'User',
 // 正方形なら size、縦横別なら w/h を使う
 'size' => null,
 'w' => null,
 'h' => null,
 'rounded' => 'md',   // 'md' | 'xl' | 'full'
 'fit' => 'cover',    // 'cover' | 'contain'
])


@php
 $width  = (int) ($w ?? $size ?? 40);
 $height = (int) ($h ?? $size ?? 40);


 $hash = hexdec(substr(md5($name), 0, 6));
 $hue  = $hash % 360;
 $bg   = "hsl($hue, 60%, 60%)";


 $radius = [
   'md'   => '10px',
   'xl'   => '16px',
   'full' => '9999px',
 ][$rounded] ?? '10px';
@endphp


@if ($url)
 <img
   src="{{ $url }}"
   alt="{{ $name }} avatar"
   style="width:{{$width}}px;height:{{$height}}px;object-fit:{{$fit}};border-radius:{{$radius}}"
 >
@else
 {{-- シルエットSVG（背景は名前ベースの色） --}}
 <svg
   width="{{ $width }}" height="{{ $height }}"
   viewBox="0 0 64 64" preserveAspectRatio="xMidYMid meet"
   role="img" aria-label="{{ $name }} placeholder avatar"
   style="display:block;border-radius:{{$radius}};background:{{$bg}}"
 >
   <circle cx="32" cy="24" r="14" fill="rgba(255,255,255,.92)"/>
   <rect x="10" y="38" width="44" height="18" rx="9" fill="rgba(255,255,255,.92)"/>
 </svg>
@endif
