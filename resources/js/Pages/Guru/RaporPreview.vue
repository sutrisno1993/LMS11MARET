<template>
  <Head title="Preview & Edit Deskripsi Rapor" />

  <AppLayout
    title="Deskripsi Capaian Kompetensi"
    :subtitle="`Siswa: ${siswa.nama} (${siswa.nis}) — ${siswa.kelas}`"
    :navigation="navigation"
  >
    <template #topbar-actions>
      <a href="/guru/nilai-akhir" class="px-4 py-2 text-xs font-semibold rounded-lg border border-white/10 text-slate-400 hover:text-white hover:bg-white/5 transition-colors">
        ⬅ Kembali ke Kalkulasi
      </a>
      <button class="px-4 py-2 bg-indigo-600 hover:bg-indigo-500 rounded-lg text-sm font-semibold text-white transition-colors shadow-lg shadow-indigo-500/30 flex items-center gap-2">
        <span>💾</span> Simpan Deskripsi
      </button>
    </template>

    <div class="grid grid-cols-3 gap-6 max-w-6xl">
      
      <!-- LEFT PANEL: Auto Generator Info -->
      <div class="col-span-1 space-y-6">
        
        <div class="rounded-2xl border border-white/8 p-5 bg-gradient-to-br from-indigo-500/20 to-purple-500/10 shadow-xl">
          <div class="text-center mb-4">
            <div class="w-16 h-16 mx-auto bg-indigo-500/20 rounded-full flex items-center justify-center text-3xl mb-2">
              🤖
            </div>
            <h3 class="font-bold text-white">AI Auto-Generate</h3>
            <p class="text-xs text-indigo-200 mt-1">Sistem Kurikulum Merdeka otomatis menyusun deskripsi berdasarkan nilai TP tertinggi dan terendah.</p>
          </div>
          
          <div class="space-y-3 mt-5">
            <div class="bg-black/20 rounded-xl p-3 border border-white/5">
              <div class="text-[10px] font-bold text-green-400 uppercase tracking-widest mb-1">Capaian Tertinggi (TP 2)</div>
              <div class="text-xs text-slate-300">Merancang arsitektur jaringan sesuai dengan standar keamanan fisik. <span class="font-bold text-green-400">(92)</span></div>
            </div>
            
            <div class="bg-black/20 rounded-xl p-3 border border-white/5">
              <div class="text-[10px] font-bold text-red-400 uppercase tracking-widest mb-1">Capaian Terendah (TP 1)</div>
              <div class="text-xs text-slate-300">Menganalisis kebutuhan perangkat keras berdasarkan topologi Star dan Tree. <span class="font-bold text-red-400">(80)</span></div>
            </div>
          </div>
          
          <button @click="generateUlang" class="w-full mt-4 py-2.5 bg-indigo-500/20 hover:bg-indigo-500/30 text-indigo-300 border border-indigo-500/30 rounded-xl text-xs font-bold transition-colors">
            🔄 Generate Ulang Teks
          </button>
        </div>

        <!-- Student Info Card -->
        <div class="rounded-2xl border border-white/8 p-5" style="background: var(--card)">
          <div class="text-sm font-bold text-slate-400 mb-3">Ringkasan Nilai Akhir</div>
          <div class="flex items-center justify-between py-2 border-b border-white/5">
            <span class="text-xs text-slate-400">Nilai Rata-rata TP</span>
            <span class="text-sm font-bold text-white">86</span>
          </div>
          <div class="flex items-center justify-between py-2 border-b border-white/5">
            <span class="text-xs text-slate-400">Nilai SAS</span>
            <span class="text-sm font-bold text-white">88</span>
          </div>
          <div class="flex items-center justify-between py-2">
            <span class="text-xs text-indigo-300 font-bold">NILAI RAPOR</span>
            <span class="text-xl font-black text-indigo-400">87</span>
          </div>
        </div>
      </div>

      <!-- RIGHT PANEL: Editor Deskripsi -->
      <div class="col-span-2 space-y-6">
        
        <div class="rounded-2xl border border-white/8 overflow-hidden" style="background: var(--card)">
          <div class="bg-white/4 px-5 py-4 border-b border-white/8 flex items-center justify-between">
            <div>
              <h3 class="font-bold text-sm">Editor Deskripsi Rapor (Manual Override)</h3>
              <p class="text-xs text-slate-500">Anda dapat mengubah redaksi teks yang digenerate oleh sistem di bawah ini.</p>
            </div>
            <div class="text-[10px] bg-green-500/10 text-green-400 px-2 py-1 rounded border border-green-500/20 uppercase font-bold tracking-widest">
              Mapel: Dasar Jaringan
            </div>
          </div>
          
          <div class="p-5">
            <label class="block text-[11px] font-bold text-slate-400 uppercase tracking-widest mb-2">
              Teks Deskripsi yang akan dicetak di rapor:
            </label>
            <textarea 
              v-model="deskripsiTeks"
              rows="6"
              class="w-full bg-black/20 border border-white/10 rounded-xl p-4 text-sm leading-relaxed text-slate-200 focus:border-indigo-500/50 focus:ring-1 focus:ring-indigo-500/50 outline-none transition-all resize-y"
            ></textarea>
            
            <div class="mt-3 flex items-start gap-2 p-3 rounded-lg bg-yellow-500/10 border border-yellow-500/20">
              <span class="text-yellow-400 text-lg">⚠️</span>
              <p class="text-xs text-yellow-200/70">
                Sesuai panduan Kurikulum Merdeka, hindari penggunaan kata negatif. Gunakan frasa <strong>"menunjukkan penguasaan yang sangat baik dalam..."</strong> untuk capaian tinggi, dan <strong>"perlu pendampingan lebih lanjut pada..."</strong> untuk capaian terendah.
              </p>
            </div>
          </div>
        </div>

        <!-- Preview Kartu Rapor -->
        <div class="rounded-2xl bg-white text-black p-8 shadow-2xl relative overflow-hidden">
          <div class="absolute inset-0 opacity-5 pointer-events-none" style="background-image: radial-gradient(#000 1px, transparent 1px); background-size: 10px 10px;"></div>
          
          <div class="text-center border-b-2 border-black/20 pb-4 mb-4 relative z-10">
            <h4 class="font-serif font-bold text-lg uppercase tracking-wider">Preview Tampilan Rapor</h4>
            <div class="text-xs font-serif text-slate-600">Bagian Capaian Kompetensi Mata Pelajaran</div>
          </div>

          <table class="w-full border-collapse border border-black/30 text-sm font-serif relative z-10">
            <thead>
              <tr class="bg-gray-100">
                <th class="border border-black/30 px-3 py-2 text-left w-1/3">Mata Pelajaran</th>
                <th class="border border-black/30 px-3 py-2 text-center w-16">Nilai Akhir</th>
                <th class="border border-black/30 px-3 py-2 text-left">Capaian Kompetensi</th>
              </tr>
            </thead>
            <tbody>
              <tr>
                <td class="border border-black/30 px-3 py-2 font-bold align-top">Dasar-Dasar Jaringan Komputer</td>
                <td class="border border-black/30 px-3 py-2 text-center font-bold text-lg align-top">87</td>
                <td class="border border-black/30 px-3 py-2 leading-relaxed text-justify align-top">
                  {{ deskripsiTeks }}
                </td>
              </tr>
            </tbody>
          </table>
        </div>

      </div>
    </div>
  </AppLayout>
</template>

<script setup>
import { Head } from '@inertiajs/vue3';
import { ref } from 'vue';
import AppLayout from '@/Layouts/AppLayout.vue';

const navigation = [
  {
    label: 'KBM (Kegiatan Belajar Mengajar)',
    items: [
      { href: '/guru/dashboard', icon: '📊', label: 'Dashboard' },
      { href: '/guru/riwayat-jurnal', icon: '📜', label: 'Riwayat Jurnal Mengajar' },
    ],
  },
  {
    label: 'Evaluasi & Penilaian',
    items: [
      { href: '/guru/pemetaan-materi', icon: '🗺️', label: 'Pemetaan Materi' },
      { href: '/guru/bank-soal', icon: '📝', label: 'Bank Soal & Ujian Live' },
      { href: '/guru/nilai-sumatif', icon: '📊', label: 'Nilai Sumatif' },
      { href: '/guru/nilai-akhir', icon: '📋', label: 'Nilai Akhir' },
      { href: '/guru/rapor-preview', icon: '📑', label: 'Rapor Preview' },
    ],
  },
];

const siswa = ref({
  id: 1,
  nama: 'Ahmad Rifai',
  nis: '2024001',
  kelas: 'XII TKJ 1'
});

const defaultText = "Menunjukkan penguasaan yang sangat baik dalam merancang arsitektur jaringan sesuai dengan standar keamanan fisik. Namun, perlu pendampingan lebih lanjut pada kemampuan menganalisis kebutuhan perangkat keras berdasarkan topologi Star dan Tree.";

const deskripsiTeks = ref(defaultText);

const generateUlang = () => {
  deskripsiTeks.value = "Siswa menunjukkan pemahaman yang sangat mendalam terkait perancangan arsitektur jaringan dan standar keamanan fisik. Disarankan untuk terus meningkatkan kemampuan analisis perangkat keras pada berbagai topologi jaringan yang lebih kompleks.";
};
</script>
