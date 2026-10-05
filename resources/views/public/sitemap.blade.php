{!! '<'.'?xml version="1.0" encoding="UTF-8"?>' !!}
<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">
    <url><loc>{{ route('welcome') }}</loc></url>
    @foreach ($gyms as $gym)
        <url><loc>{{ route('public.gym', $gym->slug) }}</loc><lastmod>{{ $gym->updated_at->toAtomString() }}</lastmod></url>
    @endforeach
</urlset>
