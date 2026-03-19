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
</head>
<body>
<main>
    <h1>{{ $site->name }}</h1>
    @if($site->bio)
        <p>{{ $site->bio }}</p>
    @endif
    @foreach($blocks as $block)
        @if($block->type === 'text' && !empty($block->props['content']))
            <p>{{ $block->props['content'] }}</p>
        @endif
    @endforeach
</main>
</body>
</html>
