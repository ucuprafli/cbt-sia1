<x-filament-panels::page>
    @php
        $total = count($questions);
        $totalPages = $this->totalPages;
        $pageQuestions = $this->pageQuestions;
        $answered = collect($questions)->filter(fn ($q) => ! empty($q['selected_answer_id']))->count();
        $unanswered = $total - $answered;
        $progress = $total > 0 ? round(($answered / $total) * 100) : 0;
        $firstOnPage = array_key_first($pageQuestions);
        $lastOnPage = array_key_last($pageQuestions);
        $isLastPage = $page >= $totalPages;
    @endphp

    {{-- ====== CSS khusus halaman ujian (tidak butuh npm run build) ====== --}}
    <style>
        .cbt-exam {
            --cbt-surface: #ffffff;
            --cbt-surface-2: #f4f4f5;
            --cbt-border: #e4e4e7;
            --cbt-text: #18181b;
            --cbt-muted: #71717a;
            --cbt-primary: #4f46e5;
            --cbt-primary-text: #4f46e5;
            --cbt-primary-soft: rgba(79, 70, 229, .08);
            --cbt-success: #16a34a;
            --cbt-success-soft: rgba(22, 163, 74, .12);
            --cbt-danger: #dc2626;
            color: var(--cbt-text);
        }
        .dark .cbt-exam {
            --cbt-surface: #18181b;
            --cbt-surface-2: #27272a;
            --cbt-border: #3f3f46;
            --cbt-text: #f4f4f5;
            --cbt-muted: #a1a1aa;
            --cbt-primary-text: #a5b4fc;
            --cbt-primary-soft: rgba(129, 140, 248, .16);
            --cbt-success: #4ade80;
            --cbt-success-soft: rgba(74, 222, 128, .14);
            --cbt-danger: #f87171;
        }
        .cbt-exam [x-cloak] { display: none !important; }
        .cbt-exam *, .cbt-exam *::before, .cbt-exam *::after { box-sizing: border-box; }

        /* Top bar: judul, progres, timer */
        .cbt-topbar {
            position: sticky; top: 4.5rem; z-index: 10;
            display: flex; flex-wrap: wrap; align-items: center; justify-content: space-between; gap: .75rem 1.5rem;
            padding: .85rem 1.1rem; margin-bottom: 1.25rem;
            background: var(--cbt-surface); border: 1px solid var(--cbt-border); border-radius: .9rem;
            box-shadow: 0 4px 14px rgba(0, 0, 0, .12);
        }
        .cbt-topbar-title { font-size: 1rem; font-weight: 600; line-height: 1.3; }
        .cbt-topbar-sub { font-size: .8rem; color: var(--cbt-muted); margin-top: .1rem; }
        .cbt-timer {
            display: flex; align-items: center; gap: .6rem;
            padding: .45rem .9rem; border-radius: .6rem; background: var(--cbt-surface-2);
        }
        .cbt-timer-label { font-size: .75rem; color: var(--cbt-muted); }
        .cbt-timer-value { font-family: ui-monospace, SFMono-Regular, Menlo, monospace; font-size: 1.25rem; font-weight: 700; }
        .cbt-timer.is-low .cbt-timer-value { color: var(--cbt-danger); }
        .cbt-progress { height: .35rem; background: var(--cbt-surface-2); border-radius: 999px; overflow: hidden; width: 100%; }
        .cbt-progress > span { display: block; height: 100%; background: var(--cbt-success); border-radius: 999px; transition: width .25s; }
        .cbt-topbar-progress { flex: 1 1 100%; }

        /* Layout 2 kolom */
        .cbt-layout { display: grid; grid-template-columns: minmax(0, 1fr); gap: 1.25rem; align-items: start; }
        @media (min-width: 1024px) {
            .cbt-layout { grid-template-columns: minmax(0, 1fr) 17rem; }
            .cbt-aside { position: sticky; top: 10.5rem; max-height: calc(100vh - 12rem); overflow-y: auto; }
        }
        .cbt-questions { display: flex; flex-direction: column; gap: 1.1rem; min-width: 0; }

        /* Kartu soal */
        .cbt-card {
            background: var(--cbt-surface); border: 1px solid var(--cbt-border); border-radius: .9rem;
            padding: 1.25rem; scroll-margin-top: 10.5rem;
        }
        .cbt-q-head { display: flex; align-items: center; gap: .65rem; margin-bottom: .9rem; }
        .cbt-q-no {
            display: inline-flex; align-items: center; justify-content: center; flex-shrink: 0;
            min-width: 2.1rem; height: 2.1rem; padding: 0 .5rem; border-radius: .55rem;
            background: var(--cbt-primary); color: #fff; font-weight: 700; font-size: .9rem;
        }
        .cbt-q-subject { font-size: .75rem; font-weight: 600; letter-spacing: .04em; text-transform: uppercase; color: var(--cbt-primary-text); }
        .cbt-badge {
            margin-left: auto; font-size: .7rem; font-weight: 600; padding: .15rem .55rem; border-radius: 999px;
            background: var(--cbt-success-soft); color: var(--cbt-success);
        }

        /* Isi soal (HTML dari editor) */
        .cbt-prose { font-size: .95rem; line-height: 1.65; overflow-wrap: anywhere; }
        .cbt-prose p { margin: 0 0 .6rem; }
        .cbt-prose img { max-width: 100%; height: auto; border-radius: .5rem; }
        .cbt-prose ul { list-style: disc; padding-left: 1.4rem; margin: 0 0 .6rem; }
        .cbt-prose ol { list-style: decimal; padding-left: 1.4rem; margin: 0 0 .6rem; }
        .cbt-prose table { border-collapse: collapse; max-width: 100%; display: block; overflow-x: auto; }
        .cbt-prose th, .cbt-prose td { border: 1px solid var(--cbt-border); padding: .3rem .6rem; }

        /* Opsi jawaban */
        .cbt-options { display: flex; flex-direction: column; gap: .55rem; margin-top: 1rem; }
        .cbt-option {
            display: flex; align-items: center; gap: .8rem; width: 100%; text-align: left;
            padding: .7rem .9rem; cursor: pointer; font: inherit; color: inherit;
            background: transparent; border: 1.5px solid var(--cbt-border); border-radius: .7rem;
            transition: border-color .15s, background .15s;
        }
        .cbt-option:hover { border-color: var(--cbt-primary); background: var(--cbt-primary-soft); }
        .cbt-option:focus-visible { outline: 2px solid var(--cbt-primary); outline-offset: 2px; }
        .cbt-option.is-selected { border-color: var(--cbt-primary); background: var(--cbt-primary-soft); }
        .cbt-letter {
            display: inline-flex; align-items: center; justify-content: center; flex-shrink: 0;
            width: 1.9rem; height: 1.9rem; border-radius: 999px; font-size: .8rem; font-weight: 700;
            border: 1.5px solid var(--cbt-muted); color: var(--cbt-muted);
        }
        .cbt-option.is-selected .cbt-letter { background: var(--cbt-primary); border-color: var(--cbt-primary); color: #fff; }
        .cbt-opt-text { font-size: .92rem; line-height: 1.5; overflow-wrap: anywhere; }

        /* Footer paginasi */
        .cbt-pager {
            display: flex; flex-wrap: wrap; align-items: center; justify-content: space-between; gap: .75rem;
            padding: 1rem 1.1rem; background: var(--cbt-surface); border: 1px solid var(--cbt-border); border-radius: .9rem;
        }
        .cbt-pager-info { font-size: .85rem; color: var(--cbt-muted); }
        .cbt-btn {
            display: inline-flex; align-items: center; justify-content: center; gap: .4rem;
            padding: .55rem 1.1rem; border-radius: .6rem; border: 1px solid var(--cbt-border);
            background: transparent; color: var(--cbt-text); font: inherit; font-size: .88rem; font-weight: 600; cursor: pointer;
        }
        .cbt-btn:hover:not(:disabled) { background: var(--cbt-surface-2); }
        .cbt-btn:disabled { opacity: .4; cursor: not-allowed; }
        .cbt-btn-primary { background: var(--cbt-primary); border-color: var(--cbt-primary); color: #fff; }
        .cbt-btn-primary:hover:not(:disabled) { background: var(--cbt-primary); filter: brightness(1.1); }
        .cbt-btn-success { background: #16a34a; border-color: #16a34a; color: #fff; }
        .cbt-btn-success:hover:not(:disabled) { background: #16a34a; filter: brightness(1.1); }

        /* Panel navigasi */
        .cbt-aside-card { background: var(--cbt-surface); border: 1px solid var(--cbt-border); border-radius: .9rem; padding: 1rem; }
        .cbt-aside-title { font-size: .72rem; font-weight: 600; letter-spacing: .06em; text-transform: uppercase; color: var(--cbt-muted); margin-bottom: .6rem; }
        .cbt-aside-count { font-size: .9rem; font-weight: 600; margin-bottom: .5rem; }
        .cbt-nav-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(2.5rem, 1fr)); gap: .45rem; margin-top: .9rem; }
        .cbt-nav-btn {
            height: 2.5rem; display: flex; align-items: center; justify-content: center; cursor: pointer;
            border-radius: .5rem; border: 1.5px solid var(--cbt-border); background: var(--cbt-surface-2);
            color: var(--cbt-muted); font: inherit; font-size: .82rem; font-weight: 700;
        }
        .cbt-nav-btn:hover { border-color: var(--cbt-primary); }
        .cbt-nav-btn.is-answered { background: var(--cbt-success-soft); border-color: var(--cbt-success); color: var(--cbt-success); }
        .cbt-nav-btn.is-page { box-shadow: 0 0 0 2px var(--cbt-primary); }
        .cbt-legend { display: flex; flex-wrap: wrap; gap: .4rem .9rem; margin-top: .9rem; font-size: .72rem; color: var(--cbt-muted); }
        .cbt-legend i { display: inline-block; width: .7rem; height: .7rem; border-radius: .2rem; margin-right: .3rem; vertical-align: -1px; border: 1.5px solid var(--cbt-border); background: var(--cbt-surface-2); }
        .cbt-legend i.ans { background: var(--cbt-success-soft); border-color: var(--cbt-success); }
        .cbt-legend i.pg { border-color: var(--cbt-primary); }

        .cbt-empty { padding: 2rem; text-align: center; color: var(--cbt-muted); background: var(--cbt-surface); border: 1px solid var(--cbt-border); border-radius: .9rem; }

        /* Modal peringatan */
        .cbt-overlay { position: fixed; inset: 0; z-index: 100; display: flex; align-items: center; justify-content: center; padding: 1rem; background: rgba(0, 0, 0, .65); }
        .cbt-modal { max-width: 24rem; padding: 1.5rem; text-align: center; background: var(--cbt-surface); border: 1px solid var(--cbt-danger); border-radius: .9rem; }
        .cbt-modal h3 { margin: 0; font-size: 1rem; font-weight: 700; color: var(--cbt-danger); }
        .cbt-modal p { margin: .6rem 0 1rem; font-size: .9rem; line-height: 1.5; }
        .cbt-btn-danger { background: #dc2626; border-color: #dc2626; color: #fff; }

        @media (max-width: 640px) {
            .cbt-card { padding: 1rem; }
            .cbt-pager { flex-direction: column; align-items: stretch; text-align: center; }
        }
    </style>

    <div
        class="cbt-exam"
        x-data="{
            secondsLeft: {{ $this->remainingSeconds }},
            violationLocked: false,
            showWarning: false,
            warningText: '',
            init() {
                this.tick();
                this.watchExit();
            },
            tick() {
                setInterval(() => {
                    if (this.secondsLeft <= 0) return;
                    this.secondsLeft--;
                    if (this.secondsLeft === 0) {
                        this.violationLocked = true;
                        $wire.timeUp();
                    }
                }, 1000);
            },
            formatted() {
                const m = Math.floor(this.secondsLeft / 60).toString().padStart(2, '0');
                const s = (this.secondsLeft % 60).toString().padStart(2, '0');
                return m + ':' + s;
            },
            reportExit() {
                if (this.violationLocked) return;
                $wire.recordViolation();
            },
            watchExit() {
                document.addEventListener('visibilitychange', () => {
                    if (document.hidden) this.reportExit();
                });
                window.addEventListener('blur', () => this.reportExit());
                window.addEventListener('beforeunload', (e) => {
                    if (this.violationLocked) return;
                    e.preventDefault();
                    e.returnValue = '';
                });
            }
        }"
        x-on:violation-warning.window="
            warningText = 'Dilarang keluar dari halaman ujian. Keluar ' + $event.detail.remaining + 'x lagi maka ujian akan dihentikan dan nilai langsung keluar.';
            showWarning = true;
        "
        x-on:exam-finished.window="violationLocked = true"
        x-on:scroll-to-question.window="
            setTimeout(() => {
                const el = document.getElementById('soal-' + $event.detail.index);
                if (el) el.scrollIntoView({ behavior: 'smooth', block: 'start' });
            }, 80);
        "
    >
        {{-- Peringatan pelanggaran --}}
        <div x-show="showWarning" x-cloak class="cbt-overlay">
            <div class="cbt-modal">
                <h3>Peringatan</h3>
                <p x-text="warningText"></p>
                <button type="button" x-on:click="showWarning = false" class="cbt-btn cbt-btn-danger">
                    Saya mengerti
                </button>
            </div>
        </div>

        {{-- Top bar: judul, timer, progres --}}
        <div class="cbt-topbar">
            <div>
                <div class="cbt-topbar-title">{{ $exam->type?->getLabel() }} &middot; {{ $exam->title }}</div>
                <div class="cbt-topbar-sub">
                    @if ($total > 0)
                        Soal {{ $firstOnPage + 1 }}&ndash;{{ $lastOnPage + 1 }} dari {{ $total }}
                        @if ($totalPages > 1) &middot; Halaman {{ $page }}/{{ $totalPages }} @endif
                    @endif
                </div>
            </div>
            <div class="cbt-timer" x-bind:class="{ 'is-low': secondsLeft < 300 }">
                <span class="cbt-timer-label">Sisa waktu</span>
                <span class="cbt-timer-value" x-text="formatted()"></span>
            </div>
            <div class="cbt-topbar-progress">
                <div class="cbt-progress"><span @style(['width: ' . $progress . '%'])></span></div>
            </div>
        </div>

        @if ($total === 0)
            <div class="cbt-empty">Belum ada soal pada ujian ini. Hubungi pengawas/guru.</div>
        @else
            <div class="cbt-layout">
                {{-- Daftar soal (semua soal di halaman ini tampil berurutan ke bawah) --}}
                <div class="cbt-questions">
                    @foreach ($pageQuestions as $i => $q)
                        <article id="soal-{{ $i }}" wire:key="soal-{{ $q['attempt_answer_id'] }}" class="cbt-card">
                            <div class="cbt-q-head">
                                <span class="cbt-q-no">{{ $i + 1 }}</span>
                                <span class="cbt-q-subject">{{ $q['subject'] }}</span>
                                @if (! empty($q['selected_answer_id']))
                                    <span class="cbt-badge">Terjawab</span>
                                @endif
                            </div>

                            <div class="cbt-prose">{!! $q['payload'] !!}</div>

                            <div class="cbt-options" role="radiogroup">
                                @foreach ($q['options'] as $n => $option)
                                    @php $selected = $q['selected_answer_id'] === $option['id']; @endphp
                                    <button
                                        type="button"
                                        role="radio"
                                        aria-checked="{{ $selected ? 'true' : 'false' }}"
                                        wire:key="opt-{{ $q['attempt_answer_id'] }}-{{ $option['id'] }}"
                                        wire:click="selectAnswer({{ $q['attempt_answer_id'] }}, {{ $option['id'] }})"
                                        class="cbt-option {{ $selected ? 'is-selected' : '' }}"
                                    >
                                        <span class="cbt-letter">{{ chr(65 + $n) }}</span>
                                        <span class="cbt-opt-text">{{ $option['text'] }}</span>
                                    </button>
                                @endforeach
                            </div>
                        </article>
                    @endforeach

                    {{-- Paginasi: hanya relevan kalau soal > perPage --}}
                    <div class="cbt-pager">
                        @if ($totalPages > 1)
                            <button type="button" wire:click="previousPage" class="cbt-btn" @disabled($page <= 1)>
                                &larr; Halaman sebelumnya
                            </button>
                            <span class="cbt-pager-info">Halaman {{ $page }} dari {{ $totalPages }}</span>
                        @else
                            <span class="cbt-pager-info">
                                @if ($unanswered > 0) {{ $unanswered }} soal belum dijawab @else Semua soal sudah dijawab @endif
                            </span>
                        @endif

                        @if ($isLastPage)
                            <button
                                type="button"
                                class="cbt-btn cbt-btn-success"
                                x-on:click="if (confirm('Kirim semua jawaban sekarang? {{ $unanswered }} soal belum dijawab akan dianggap kosong.')) { violationLocked = true; $wire.submit(); }"
                            >
                                Kirim Jawaban
                            </button>
                        @else
                            <button type="button" wire:click="nextPage" class="cbt-btn cbt-btn-primary">
                                Halaman selanjutnya &rarr;
                            </button>
                        @endif
                    </div>
                </div>

                {{-- Navigasi nomor soal --}}
                <aside class="cbt-aside">
                    <div class="cbt-aside-card">
                        <div class="cbt-aside-title">Navigasi soal</div>
                        <div class="cbt-aside-count">Terjawab {{ $answered }} / {{ $total }}</div>
                        <div class="cbt-progress"><span @style(['width: ' . $progress . '%'])></span></div>

                        <div class="cbt-nav-grid">
                            @foreach ($questions as $i => $q)
                                <button
                                    type="button"
                                    wire:key="nav-{{ $q['attempt_answer_id'] }}"
                                    wire:click="goTo({{ $i }})"
                                    class="cbt-nav-btn {{ ! empty($q['selected_answer_id']) ? 'is-answered' : '' }} {{ ($i >= $firstOnPage && $i <= $lastOnPage) ? 'is-page' : '' }}"
                                >
                                    {{ $i + 1 }}
                                </button>
                            @endforeach
                        </div>

                        <div class="cbt-legend">
                            <span><i class="ans"></i>Terjawab</span>
                            <span><i></i>Belum</span>
                            <span><i class="pg"></i>Halaman ini</span>
                        </div>
                    </div>
                </aside>
            </div>
        @endif
    </div>
</x-filament-panels::page>