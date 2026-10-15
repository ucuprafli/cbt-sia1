<x-filament-panels::page>
    {{-- ====== CSS khusus halaman Histori Pelajaran (tidak butuh npm run build) ====== --}}
    <style>
        .cbt-hist {
            --h-surface: #ffffff;
            --h-surface-2: #f4f4f5;
            --h-border: #e4e4e7;
            --h-text: #18181b;
            --h-muted: #71717a;
            --h-primary: #4f46e5;
            --h-success: #16a34a;
            --h-success-soft: rgba(22, 163, 74, .12);
            --h-warn: #d97706;
            --h-warn-soft: rgba(217, 119, 6, .12);
            --h-danger: #dc2626;
            --h-danger-soft: rgba(220, 38, 38, .12);
            color: var(--h-text);
            display: flex; flex-direction: column; gap: 1rem;
        }
        .dark .cbt-hist {
            --h-surface: #18181b;
            --h-surface-2: #27272a;
            --h-border: #3f3f46;
            --h-text: #f4f4f5;
            --h-muted: #a1a1aa;
            --h-success: #4ade80;
            --h-success-soft: rgba(74, 222, 128, .14);
            --h-warn: #fbbf24;
            --h-warn-soft: rgba(251, 191, 36, .14);
            --h-danger: #f87171;
            --h-danger-soft: rgba(248, 113, 113, .14);
        }
        .cbt-hist *, .cbt-hist *::before, .cbt-hist *::after { box-sizing: border-box; }

        .h-toolbar { display: flex; flex-wrap: wrap; gap: .75rem; align-items: center; justify-content: space-between; }
        .h-search {
            width: 100%; max-width: 20rem; padding: .55rem .85rem; font: inherit; font-size: .88rem;
            color: var(--h-text); background: var(--h-surface); border: 1px solid var(--h-border); border-radius: .6rem;
        }
        .h-search:focus { outline: 2px solid var(--h-primary); outline-offset: 1px; }
        .h-count { font-size: .8rem; color: var(--h-muted); }

        .h-wrap { overflow-x: auto; background: var(--h-surface); border: 1px solid var(--h-border); border-radius: .9rem; }
        .h-table { width: 100%; border-collapse: collapse; font-size: .88rem; white-space: nowrap; }
        .h-table th {
            padding: .8rem 1rem; text-align: left; font-size: .7rem; font-weight: 600; letter-spacing: .06em;
            text-transform: uppercase; color: var(--h-muted); background: var(--h-surface-2);
            border-bottom: 1px solid var(--h-border);
        }
        .h-table td { padding: .8rem 1rem; border-bottom: 1px solid var(--h-border); }
        .h-table tbody tr:last-child td { border-bottom: 0; }
        .h-table tbody tr:hover td { background: var(--h-surface-2); }
        .h-center { text-align: center !important; }
        .h-name { font-weight: 600; }
        .h-muted { color: var(--h-muted); }
        .h-score {
            display: inline-block; min-width: 3.2rem; padding: .15rem .6rem; border-radius: 999px;
            font-size: .8rem; font-weight: 700; text-align: center;
        }
        .h-score.good { background: var(--h-success-soft); color: var(--h-success); }
        .h-score.mid  { background: var(--h-warn-soft); color: var(--h-warn); }
        .h-score.low  { background: var(--h-danger-soft); color: var(--h-danger); }
        .h-empty { padding: 2.5rem 1rem; text-align: center; color: var(--h-muted); }
    </style>

    @php
        $cls = fn ($v) => $v === null ? '' : ($v >= 75 ? 'good' : ($v >= 50 ? 'mid' : 'low'));
    @endphp

    <div class="cbt-hist" x-data="{ q: '', shown: {{ count($rows) }} }">
        <div class="h-toolbar">
            <input
                type="search"
                class="h-search"
                placeholder="Cari siswa, NIS, atau mata pelajaran..."
                x-model="q"
                x-on:input="$nextTick(() => shown = $refs.body.querySelectorAll('tr[data-row]:not([style*=none])').length)"
            >
            <span class="h-count"><span x-text="shown"></span> baris</span>
        </div>

        <div class="h-wrap">
            <table class="h-table">
                <thead>
                    <tr>
                        <th>Siswa</th>
                        <th>NIS</th>
                        <th>Mata Pelajaran</th>
                        <th class="h-center">Jlh. Ujian</th>
                        <th class="h-center">Rata-rata</th>
                        <th class="h-center">Tertinggi</th>
                        <th class="h-center">Terakhir</th>
                    </tr>
                </thead>
                <tbody x-ref="body">
                    @forelse ($rows as $row)
                        <tr data-row x-show="$el.innerText.toLowerCase().includes(q.toLowerCase())">
                            <td class="h-name">{{ $row['siswa'] }}</td>
                            <td class="h-muted">{{ $row['nis'] }}</td>
                            <td>{{ $row['pelajaran'] }}</td>
                            <td class="h-center">{{ $row['jumlah_ujian'] }}</td>
                            <td class="h-center"><span class="h-score {{ $cls($row['rata_rata']) }}">{{ $row['rata_rata'] }}</span></td>
                            <td class="h-center"><span class="h-score {{ $cls($row['tertinggi']) }}">{{ $row['tertinggi'] }}</span></td>
                            <td class="h-center"><span class="h-score {{ $cls($row['terakhir']) }}">{{ $row['terakhir'] }}</span></td>
                        </tr>
                    @empty
                        <tr><td colspan="7" class="h-empty">Belum ada data ujian.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</x-filament-panels::page>
