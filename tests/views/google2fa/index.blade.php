google2fa view

@if (isset($errors))
  @if (count($errors) > 0)
    @foreach ($errors->all() as $message)
        {{ $message }}<br>
    @endforeach
  @endif
@endif
