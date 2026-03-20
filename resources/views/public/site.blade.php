<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $site->name }}</title>
    <meta property="og:title" content="{{ $site->name }}">
    @if($site->bio)
        <meta name="description" content="{{ $site->bio }}">
    @endif
    @if($site->avatar_url)
        <meta property="og:image" content="{{ $site->avatar_url }}">
    @endif
    <style>
        body { font-family: system-ui, sans-serif; margin: 1rem; max-width: 42rem; }
        img { max-width: 100%; height: auto; }
        iframe { max-width: 100%; }
    </style>
</head>
<body>
<main>
    <h1>{{ $site->name }}</h1>
    @if($site->bio)
        <p>{{ $site->bio }}</p>
    @endif
    @foreach($blocks as $block)
        @php($view = 'public.blocks._'.$block->type)
        @if(view()->exists($view))
            @include($view, ['block' => $block])
        @endif
    @endforeach
</main>
</body>
</html>
