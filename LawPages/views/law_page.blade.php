<pre>
---
@foreach ($meta as $key => $value)
@if (is_array($value))
{{ $key }}:
@foreach ($value as $k => $v)
  - {{ $v }}
@endforeach
@else
{{ $key }}: {{ $value }}
@endif
@endforeach
---

{{ $text }}</pre>