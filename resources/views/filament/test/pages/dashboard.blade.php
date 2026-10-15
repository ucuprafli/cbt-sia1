<x-filament-panels::page>
    @php
        $types = $this->types;
        $summary = $this->summary;
        $labels = ['lulus' => 'Lulus', 'tidak_lulus' => 'Tidak Lulus', 'dihentikan' => 'Dihentikan'];
        $overallLabel = ['lulus' => 'Lulus', 'tidak_lulus' => 'Belum Lulus', 'dihentikan' => 'Dihentikan'];
    @endphp

    {{-- ====== CSS khusus dashboard murid (tidak butuh npm run build) ====== --}}
    <style>
        .cbt-dash {
            --d-surface: #ffffff;
            --d-surface-2: #f4f4f5;
            --d-border: #e4e4e7;
            --d-text: #18181b;
            --d-muted: #71717a;
            --d-primary: #4f46e5;
            --d-primary-soft: rgba(79, 70, 229, .10);
            --d-lulus: #16a34a;
            --d-lulus-soft: rgba(22, 163, 74, .12);
            --d-gagal: #dc2626;
            --d-gagal-soft: rgba(220, 38, 38, .12);
            --d-stop: #d97706;
            --d-stop-soft: rgba(217, 119, 6, .14);
            color: var(--d-text);
            display: flex; flex-direction: column; gap: 1.25rem;
        }
        .dark .cbt-dash {
            --d-surface: #18181b;
            --d-surface-2: #27272a;
            --d-border: #3f3f46;
            --d-text: #f4f4f5;
            --d-muted: #a1a1aa;
            --d-primary-soft: rgba(129, 140, 248, .18);
            --d-lulus: #4ade80;
            --d-lulus-soft: rgba(74, 222, 128, .14);
            --d-gagal: #f87171;
            --d-gagal-soft: rgba(248, 113, 113, .14);
            --d-stop: #fbbf24;
            --d-stop-soft: rgba(251, 191, 36, .14);
        }
        .cbt-dash *, .cbt-dash *::before, .cbt-dash *::after { box-sizing: border-box; }

        /* Sapaan */
        .d-hero {
            padding: 1.4rem 1.5rem; border-radius: 1rem; color: #fff;
            background: linear-gradient(135deg, #4f46e5 0%, #7c3aed 100%);
        }
        .d-hero h2 { margin: 0; font-size: 1.35rem; font-weight: 700; }
        .d-hero p { margin: .35rem 0 0; font-size: .9rem; opacity: .85; }

        /* Tab jenis ujian */
        .d-section-title { font-size: .72rem; font-weight: 600; letter-spacing: .06em; text-transform: uppercase; color: var(--d-muted); }
        .d-tabs { display: flex; flex-wrap: wrap; gap: .5rem; }
        .d-tab {
            padding: .5rem 1.1rem; border-radius: 999px; cursor: pointer; font: inherit; font-size: .85rem; font-weight: 600;
            color: var(--d-muted); background: var(--d-surface); border: 1.5px solid var(--d-border);
            transition: all .15s;
        }
        .d-tab:hover { border-color: var(--d-primary); color: var(--d-text); }
        .d-tab.is-active { background: var(--d-primary); border-color: var(--d-primary); color: #fff; }

        /* Layout */
        .d-grid { display: grid; grid-template-columns: minmax(0, 1fr); gap: 1.25rem; align-items: start; }
        @media (min-width: 900px) { .d-grid { grid-template-columns: 20rem minmax(0, 1fr); } }
        .d-stack { display: flex; flex-direction: column; gap: 1.25rem; }
        .d-card { background: var(--d-surface); border: 1px solid var(--d-border); border-radius: 1rem; padding: 1.4rem; }

        /* Kartu skor + ring */
        .d-score { text-align: center; }
        .d-ring {
            --p: 0;
            width: 11.5rem; aspect-ratio: 1; margin: 0 auto 1.1rem; border-radius: 50%;
            display: grid; place-items: center;
            background: conic-gradient(var(--c, var(--d-primary)) calc(var(--p) * 1%), var(--d-surface-2) 0);
        }
        .d-ring-inner {
            width: 8.8rem; height: 8.8rem; border-radius: 50%; background: var(--d-surface);
            display: flex; flex-direction: column; align-items: center; justify-content: center;
        }
        .d-ring-num { font-size: 2.3rem; font-weight: 800; line-height: 1; }
        .d-ring-sub { margin-top: .25rem; font-size: .72rem; color: var(--d-muted); }
        .d-score.is-lulus { --c: var(--d-lulus); }
        .d-score.is-tidak_lulus { --c: var(--d-gagal); }
        .d-score.is-dihentikan { --c: var(--d-stop); }
        .d-exam-name { font-size: .95rem; font-weight: 600; margin-top: .75rem; }
        .d-meta { font-size: .78rem; color: var(--d-muted); margin-top: .25rem; }

        /* Chip status */
        .d-chip {
            display: inline-flex; align-items: center; gap: .35rem; padding: .22rem .75rem; border-radius: 999px;
            font-size: .75rem; font-weight: 700; white-space: nowrap;
        }
        .d-chip::before { content: ''; width: .45rem; height: .45rem; border-radius: 50%; background: currentColor; }
        .d-chip.lulus { background: var(--d-lulus-soft); color: var(--d-lulus); }
        .d-chip.tidak_lulus { background: var(--d-gagal-soft); color: var(--d-gagal); }
        .d-chip.dihentikan { background: var(--d-stop-soft); color: var(--d-stop); }

        /* Counter */
        .d-counters { display: grid; grid-template-columns: repeat(3, minmax(0, 1fr)); gap: .6rem; }
        .d-counter { padding: .9rem .5rem; text-align: center; border-radius: .8rem; border: 1px solid var(--d-border); background: var(--d-surface); }
        .d-counter b { display: block; font-size: 1.5rem; font-weight: 800; line-height: 1.1; }
        .d-counter span { font-size: .68rem; color: var(--d-muted); }
        .d-counter.lulus b { color: var(--d-lulus); }
        .d-counter.tidak_lulus b { color: var(--d-gagal); }
        .d-counter.dihentikan b { color: var(--d-stop); }

        /* Daftar pelajaran */
        .d-subjects-head { display: flex; align-items: center; justify-content: space-between; margin-bottom: 1rem; }
        .d-subjects-head h3 { margin: 0; font-size: 1rem; font-weight: 700; }
        .d-list { list-style: none; margin: 0; padding: 0; display: flex; flex-direction: column; gap: .7rem; }
        .d-item { padding: .9rem 1rem; border: 1px solid var(--d-border); border-radius: .85rem; background: var(--d-surface-2); }
        .d-item-top { display: flex; align-items: center; justify-content: space-between; gap: .75rem; }
        .d-item-name { font-weight: 600; font-size: .95rem; }
        .d-item-exam { font-size: .72rem; color: var(--d-muted); margin-top: .1rem; }
        .d-bar-row { display: flex; align-items: center; gap: .75rem; margin-top: .7rem; }
        .d-bar { flex: 1; height: .5rem; border-radius: 999px; background: var(--d-border); overflow: hidden; }
        .d-bar > span { display: block; height: 100%; border-radius: 999px; }
        .d-bar.lulus > span { background: var(--d-lulus); }
        .d-bar.tidak_lulus > span { background: var(--d-gagal); }
        .d-bar.dihentikan > span { background: var(--d-stop); }
        .d-item-score { font-size: .9rem; font-weight: 700; min-width: 3rem; text-align: right; }

        .d-btn {
            display: inline-flex; align-items: center; justify-content: center; gap: .4rem; width: 100%;
            padding: .75rem 1.2rem; border-radius: .75rem; background: var(--d-primary); color: #fff;
            font-size: .88rem; font-weight: 600; text-decoration: none;
        }
        .d-btn:hover { filter: brightness(1.1); }
        .d-btn.ghost { background: transparent; color: var(--d-primary); border: 1.5px solid var(--d-primary); width: auto; }

        .d-empty { text-align: center; padding: 2.5rem 1.5rem; }
        .d-empty-icon { font-size: 2.5rem; margin-bottom: .5rem; }
        .d-empty h3 { margin: 0 0 .35rem; font-size: 1.05rem; font-weight: 700; }
        .d-empty p { margin: 0 0 1.1rem; font-size: .88rem; color: var(--d-muted); }
        .d-sub-empty { padding: 1.5rem; text-align: center; font-size: .88rem; color: var(--d-muted); }
    </style>

    <div class="cbt-dash">
        {{-- Sapaan --}}
        <div class="d-hero">
            <h2>Halo, {{ $this->studentName }}!</h2>
            <p>Ini ringkasan nilai ujianmu. Rincian lengkap ada di menu Hasil Ujian.</p>
        </div>

        @if (empty($types))
            <div class="d-card d-empty">
                <div class="d-empty-icon">&#128221;</div>
                <h3>Belum ada nilai ujian</h3>
                <p>Nilai akan muncul di sini setelah kamu menyelesaikan ujian.</p>
                <a class="d-btn ghost" href="{{ \App\Filament\Test\Resources\Exams\ExamResource::getUrl('index') }}">Lihat Sesi Ujian</a>
            </div>
        @else
            {{-- Pilih jenis ujian --}}
            <div>
                <div class="d-section-title" style="margin-bottom:.6rem">Nilai Ujian</div>
                <div class="d-tabs">
                    @foreach ($types as $t)
                        <button
                            type="button"
                            wire:key="tab-{{ $t['value'] }}"
                            wire:click="selectType('{{ $t['value'] }}')"
                            class="d-tab {{ $this->type === $t['value'] ? 'is-active' : '' }}"
                        >{{ $t['label'] }}</button>
                    @endforeach
                </div>
            </div>

            @if ($summary)
                <div class="d-grid">
                    <div class="d-stack">
                        {{-- Nilai keseluruhan --}}
                        <div class="d-card d-score is-{{ $summary['state'] }}">
                            <div class="d-ring" @style(['--p: ' . min(100, max(0, $summary['overall']))])>
                                <div class="d-ring-inner">
                                    <span class="d-ring-num">{{ number_format($summary['overall'], 0) }}</span>
                                    <span class="d-ring-sub">dari 100</span>
                                </div>
                            </div>
                            <span class="d-chip {{ $summary['state'] }}">{{ $overallLabel[$summary['state']] }}</span>
                            <div class="d-exam-name">{{ implode(', ', $summary['exams']) }}</div>
                            <div class="d-meta">
                                Ambang batas {{ rtrim(rtrim(number_format($summary['threshold'], 2), '0'), '.') }}
                                @if ($summary['finished_at'])
                                    &middot; {{ $summary['finished_at']->format('d M Y, H:i') }}
                                @endif
                            </div>
                        </div>

                        {{-- Ringkasan jumlah --}}
                        <div class="d-counters">
                            <div class="d-counter lulus"><b>{{ $summary['lulus'] }}</b><span>Lulus</span></div>
                            <div class="d-counter tidak_lulus"><b>{{ $summary['tidak_lulus'] }}</b><span>Tidak lulus</span></div>
                            <div class="d-counter dihentikan"><b>{{ $summary['dihentikan'] }}</b><span>Dihentikan</span></div>
                        </div>

                        <a class="d-btn" href="{{ $summary['url'] }}">Lihat detail di Hasil Ujian &rarr;</a>
                    </div>

                    {{-- Per pelajaran --}}
                    <div class="d-card">
                        <div class="d-subjects-head">
                            <h3>Nilai per pelajaran</h3>
                            <span class="d-meta" style="margin:0">{{ count($summary['subjects']) }} pelajaran</span>
                        </div>

                        @if (count($summary['subjects']) === 0)
                            <div class="d-sub-empty">Belum ada rincian pelajaran.</div>
                        @else
                            <ul class="d-list">
                                @foreach ($summary['subjects'] as $row)
                                    <li class="d-item" wire:key="sub-{{ $loop->index }}">
                                        <div class="d-item-top">
                                            <div>
                                                <div class="d-item-name">{{ $row['name'] }}</div>
                                                @if (count($summary['exams']) > 1)
                                                    <div class="d-item-exam">{{ $row['exam'] }}</div>
                                                @endif
                                            </div>
                                            <span class="d-chip {{ $row['status'] }}">{{ $labels[$row['status']] }}</span>
                                        </div>
                                        <div class="d-bar-row">
                                            <div class="d-bar {{ $row['status'] }}"><span @style(['width: ' . min(100, max(0, $row['score'])) . '%'])></span></div>
                                            <div class="d-item-score">{{ rtrim(rtrim(number_format($row['score'], 2), '0'), '.') }}</div>
                                        </div>
                                    </li>
                                @endforeach
                            </ul>
                        @endif
                    </div>
                </div>
            @endif
        @endif
    </div>
</x-filament-panels::page>