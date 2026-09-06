@props(['title', 'message', 'actionLabel' => null, 'actionUrl' => null])

<div class="sf-state-panel" role="status">
    <span aria-hidden="true">O</span>
    <h2>{{ $title }}</h2>
    <p>{{ $message }}</p>
    @if($actionLabel && $actionUrl)<a class="sf-button sf-button-outline" href="{{ $actionUrl }}">{{ $actionLabel }}</a>@endif
</div>
