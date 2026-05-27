@foreach (\App\Models\Package::INTERVALS as $value => $label)
    <option value="{{ $value }}" @selected(old('interval', $selected ?? 'monthly') === $value)>{{ $label }}</option>
@endforeach
