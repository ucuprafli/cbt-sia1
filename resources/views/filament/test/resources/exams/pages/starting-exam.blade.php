<x-filament-panels::page>
    <ol>
    @foreach ($collections as $pelajaran)
        <li type="A" style="font-weight: 700">{{ $pelajaran['name'] }}</li>
        <ol>
        @foreach ($pelajaran['soals'] as $pertanyaan)
            <li type="1">
                {!! $pertanyaan['payload'] !!}
                <ol>
                    @foreach ($pertanyaan['answers'] as $jawaban)
                        <li>
                            <label>
                                <x-filament::input.radio name="jawaban_{{ $pertanyaan['id'] }}" />
                                <span>
                                    {{ $jawaban['text'] }}
                                </span>
                            </label>
                        </li>
                    @endforeach
                </ol>
            </li>
            <br>
        @endforeach
        </ol>
    @endforeach
    </ol>
</x-filament-panels::page>