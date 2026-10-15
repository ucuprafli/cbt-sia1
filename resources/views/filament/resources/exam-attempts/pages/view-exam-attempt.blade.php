<x-filament-panels::page>
    @php
        $passed = (bool) $record->passed;
        $forfeited = $record->status === 'forfeited';
        $seconds = ($record->started_at && $record->finished_at) ? (int) $record->started_at->diffInSeconds($record->finished_at, true) : 0;
        $duration = intdiv($seconds, 60) . ' mnt ' . ($seconds % 60) . ' dtk';
    @endphp

    {{-- ====== CSS khusus halaman detail rekap (tidak butuh npm run build) ====== --}}
    <style>
        .cbt-result {
            --r-surface: #ffffff;
            --r-surface-2: #f4f4f5;
            --r-border: #e4e4e7;
            --r-text: #18181b;
            --r-muted: #71717a;
            --r-primary: #4f46e5;
            --r-success: #16a34a;
            --r-success-soft: rgba(22, 163, 74, .10);
            --r-danger: #dc2626;
            --r-danger-soft: rgba(220, 38, 38, .10);
            color: var(--r-text);
            display: flex; flex-direction: column; gap: 1.1rem;
        }
        .dark .cbt-result {
            --r-surface: #18181b;
            --r-surface-2: #27272a;
            --r-border: #3f3f46;
            --r-text: #f4f4f5;
            --r-muted: #a1a1aa;
            --r-success: #4ade80;
            --r-success-soft: rgba(74, 222, 128, .12);
            --r-danger: #f87171;
            --r-danger-soft: rgba(248, 113, 113, .12);
        }
        .cbt-result *, .cbt-result *::before, .cbt-result *::after { box-sizing: border-box; }

        .r-card { background: var(--r-surface); border: 1px solid var(--r-border); border-radius: .9rem; }

        /* Kartu skor */
        .r-hero { padding: 1.75rem 1.25rem; text-align: center; }
        .r-hero.is-pass { border-color: var(--r-success); background: linear-gradient(var(--r-success-soft), var(--r-success-soft)), var(--r-surface); }
        .r-hero.is-fail { border-color: var(--r-danger); background: linear-gradient(var(--r-danger-soft), var(--r-danger-soft)), var(--r-surface); }
        .r-exam-title { font-size: .85rem; color: var(--r-muted); font-weight: 500; }
        .r-score { font-size: 3.5rem; line-height: 1.1; font-weight: 800; margin: .35rem 0 .15rem; }
        .is-pass .r-score, .is-pass .r-status { color: var(--r-success); }
        .is-fail .r-score, .is-fail .r-status { color: var(--r-danger); }
        .r-status { font-size: 1rem; font-weight: 700; }
        .r-threshold { font-size: .8rem; color: var(--r-muted); margin-top: .2rem; }
        .r-forfeit { margin-top: .8rem; font-size: .8rem; color: var(--r-danger); }

        /* Statistik */
        .r-stats { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: .75rem; }
        @media (min-width: 640px) { .r-stats { grid-template-columns: repeat(4, minmax(0, 1fr)); } }
        .r-stat { padding: 1rem; text-align: center; }
        .r-stat-label { font-size: .75rem; color: var(--r-muted); }
        .r-stat-value { margin-top: .25rem; font-size: 1.4rem; font-weight: 700; }
        .r-stat-value.ok { color: var(--r-success); }
        .r-stat-value.bad { color: var(--r-danger); }
        .r-stat-value.dim { color: var(--r-muted); }
        .r-stat-value.small { font-size: 1.05rem; padding-top: .3rem; }

        .r-note { padding: 1rem 1.1rem; font-size: .9rem; color: var(--r-muted); }

        /* Pembahasan */
        .r-section-title { font-size: .72rem; font-weight: 600; letter-spacing: .06em; text-transform: uppercase; color: var(--r-muted); margin: .4rem 0 -.2rem; }
        .r-review { display: flex; flex-direction: column; gap: 1rem; }
        .r-q { padding: 1.25rem; }
        .r-q-head { display: flex; align-items: center; gap: .6rem; margin-bottom: .8rem; }
        .r-q-no {
            display: inline-flex; align-items: center; justify-content: center;
            min-width: 2rem; height: 2rem; padding: 0 .5rem; border-radius: .5rem;
            background: var(--r-primary); color: #fff; font-weight: 700; font-size: .85rem;
        }
        .r-tag { margin-left: auto; font-size: .7rem; font-weight: 600; padding: .15rem .6rem; border-radius: 999px; }
        .r-tag.ok { background: var(--r-success-soft); color: var(--r-success); }
        .r-tag.bad { background: var(--r-danger-soft); color: var(--r-danger); }
        .r-tag.dim { background: var(--r-surface-2); color: var(--r-muted); }

        .r-prose { font-size: .95rem; line-height: 1.65; overflow-wrap: anywhere; }
        .r-prose p { margin: 0 0 .6rem; }
        .r-prose img { max-width: 100%; height: auto; border-radius: .5rem; }
        .r-prose ul { list-style: disc; padding-left: 1.4rem; margin: 0 0 .6rem; }
        .r-prose ol { list-style: decimal; padding-left: 1.4rem; margin: 0 0 .6rem; }

        .r-opts { display: flex; flex-direction: column; gap: .5rem; margin-top: 1rem; }
        .r-opt {
            display: flex; align-items: center; gap: .75rem;
            padding: .65rem .85rem; border: 1.5px solid var(--r-border); border-radius: .7rem; font-size: .92rem;
        }
        .r-letter {
            display: inline-flex; align-items: center; justify-content: center; flex-shrink: 0;
            width: 1.8rem; height: 1.8rem; border-radius: 999px; font-size: .78rem; font-weight: 700;
            border: 1.5px solid var(--r-muted); color: var(--r-muted);
        }
        .r-opt-text { flex: 1; min-width: 0; overflow-wrap: anywhere; line-height: 1.5; }
        .r-opt-flag { font-size: .72rem; font-weight: 600; white-space: nowrap; }
        .r-opt.is-correct { border-color: var(--r-success); background: var(--r-success-soft); }
        .r-opt.is-correct .r-letter { background: var(--r-success); border-color: var(--r-success); color: #fff; }
        .r-opt.is-correct .r-opt-flag { color: var(--r-success); }
        .r-opt.is-wrong { border-color: var(--r-danger); background: var(--r-danger-soft); }
        .r-opt.is-wrong .r-letter { background: var(--r-danger); border-color: var(--r-danger); color: #fff; }
        .r-opt.is-wrong .r-opt-flag { color: var(--r-danger); }
        .r-empty-note { margin-top: .6rem; font-size: .8rem; color: var(--r-muted); }

        .r-actions { display: flex; justify-content: center; }
        .r-btn {
            display: inline-flex; align-items: center; padding: .6rem 1.3rem; border-radius: .6rem;
            background: var(--r-primary); color: #fff; font-size: .88rem; font-weight: 600; text-decoration: none;
        }
        .r-btn:hover { filter: brightness(1.1); }
    
        .r-info { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 1rem; padding: 1.25rem; }
        @media (min-width: 900px) { .r-info { grid-template-columns: repeat(4, minmax(0, 1fr)); } }
        .r-info-label { font-size: .72rem; color: var(--r-muted); margin-bottom: .2rem; }
        .r-info-value { font-weight: 600; font-size: .95rem; }
    </style>

    <div class="cbt-result">
        <div class="r-card r-info">
            <div><div class="r-info-label">Siswa</div><div class="r-info-value">{{ $record->student->name }} ({{ $record->student->nis }})</div></div>
            <div><div class="r-info-label">Ujian</div><div class="r-info-value">{{ $record->exam->type?->getLabel() }} &middot; {{ $record->exam->title }}</div></div>
            <div><div class="r-info-label">Status</div><div class="r-info-value">{{ $forfeited ? 'Dihentikan (pelanggaran)' : ($passed ? 'Lulus' : 'Belum Lulus') }}</div></div>
            <div><div class="r-info-label">Lama Pengerjaan</div><div class="r-info-value">{{ $duration }}</div></div>
        </div>

        <div class="r-card r-hero {{ $passed ? 'is-pass' : 'is-fail' }}">
            <div class="r-exam-title">Nilai</div>
            <div class="r-score">{{ number_format($record->score, 2) }}</div>
            <div class="r-threshold">Ambang batas {{ $record->exam->threshold }}</div>
        </div>

        <div class="r-stats">
            <div class="r-card r-stat"><div class="r-stat-label">Benar</div><div class="r-stat-value ok">{{ $record->correct_count }}</div></div>
            <div class="r-card r-stat"><div class="r-stat-label">Salah</div><div class="r-stat-value bad">{{ $record->wrong_count }}</div></div>
            <div class="r-card r-stat"><div class="r-stat-label">Kosong</div><div class="r-stat-value dim">{{ $record->empty_count }}</div></div>
            <div class="r-card r-stat"><div class="r-stat-label">Total Soal</div><div class="r-stat-value">{{ $record->total_questions }}</div></div>
        </div>

        <div class="r-section-title">Jawaban siswa</div>
        <div class="r-review">
            @foreach ($rows as $row)
                <div class="r-card r-q">
                    <div class="r-q-head">
                        <span class="r-q-no">{{ $row['position'] }}</span>
                        <span style="font-size:.72rem;font-weight:600;letter-spacing:.04em;text-transform:uppercase;color:var(--r-muted)">{{ $row['subject'] }}</span>
                        @if (! $row['selected_answer_id'])
                            <span class="r-tag dim">Tidak dijawab</span>
                        @elseif ($row['is_correct'])
                            <span class="r-tag ok">Benar</span>
                        @else
                            <span class="r-tag bad">Salah</span>
                        @endif
                    </div>

                    <div class="r-prose">{!! $row['payload'] !!}</div>

                    <div class="r-opts">
                        @foreach ($row['options'] as $n => $opt)
                            @php
                                $isSelected = $row['selected_answer_id'] === $opt['id'];
                                $state = $opt['is_correct'] ? 'is-correct' : ($isSelected ? 'is-wrong' : '');
                            @endphp
                            <div class="r-opt {{ $state }}">
                                <span class="r-letter">{{ chr(65 + $n) }}</span>
                                <span class="r-opt-text">{{ $opt['text'] }}</span>
                                @if ($opt['is_correct'] && $isSelected)
                                    <span class="r-opt-flag">Jawaban siswa &middot; Benar</span>
                                @elseif ($opt['is_correct'])
                                    <span class="r-opt-flag">Kunci jawaban</span>
                                @elseif ($isSelected)
                                    <span class="r-opt-flag">Jawaban siswa</span>
                                @endif
                            </div>
                        @endforeach
                    </div>
                </div>
            @endforeach
        </div>
    </div>
</x-filament-panels::page>
