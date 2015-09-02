<pre>
---
@foreach ($meta as $key => $value)
    {{ $key }}: {{ $value }}
@endforeach
---
    
{{ $text }}
</pre>