@props(['type'])

<span class="badge bg-light text-dark border">
    {{ config("achat.types_articles.{$type}", $type) }}
</span>
