<template>
  <Head title="Monitoring Nilai Siswa" />

  <AppLayout
    title="Monitoring Akademik"
    subtitle="Pantau capaian nilai siswa dan teruskan ke Wali Kelas"
    :navigation="navigation"
  >
    <div class="space-y-6">
      
      <!-- Stats Header -->
      <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
        <div class="p-6 rounded-2xl border border-white/10 relative overflow-hidden" style="background: var(--card)">
          <div class="text-sm font-semibold text-slate-400 mb-2">Total Kelas Dipantau</div>
          <div class="text-4xl font-bold text-white">{{ classesData.length }}</div>
          <div class="absolute -right-4 -bottom-4 text-8xl opacity-5">🏫</div>
        </div>
        <div class="p-6 rounded-2xl border border-white/10 relative overflow-hidden" style="background: var(--card)">
          <div class="text-sm font-semibold text-slate-400 mb-2">Total Siswa</div>
          <div class="text-4xl font-bold text-indigo-400">{{ totalSemuaSiswa }}</div>
          <div class="absolute -right-4 -bottom-4 text-8xl opacity-5">👥</div>
        </div>
        <div class="p-6 rounded-2xl border border-red-500/20 bg-red-500/5 relative overflow-hidden">
          <div class="text-sm font-semibold text-red-400 mb-2">Butuh Perbaikan (< KKM 75)</div>
          <div class="text-4xl font-bold text-red-500">{{ totalSemuaKurang }}</div>
          <div class="absolute -right-4 -bottom-4 text-8xl opacity-5">⚠️</div>
        </div>
      </div>

      <!-- Class Accordions -->
      <div class="space-y-4">
        <div 
          v-for="kelas in classesData" 
          :key="kelas.id_kelas"
          class="rounded-2xl border border-white/10 overflow-hidden transition-all duration-300"
          :class="activeClass === kelas.id_kelas ? 'ring-2 ring-indigo-500/50' : 'hover:border-white/20'"
          style="background: var(--card)"
        >
          <!-- Accordion Header -->
          <div 
            @click="toggleKelas(kelas.id_kelas)"
            class="p-5 flex items-center justify-between cursor-pointer group"
            :class="activeClass === kelas.id_kelas ? 'bg-white/5' : ''"
          >
            <div class="flex items-center gap-4">
              <div class="w-12 h-12 rounded-xl bg-indigo-500/20 flex items-center justify-center text-xl text-indigo-400 font-bold group-hover:scale-110 transition-transform">
                {{ kelas.nama_kelas.substring(0, 2) }}
              </div>
              <div>
                <h3 class="text-lg font-bold text-white">{{ kelas.nama_kelas }}</h3>
                <div class="text-sm text-slate-400 flex items-center gap-2">
                  <span class="px-2 py-0.5 rounded bg-white/10 text-xs font-semibold">Wali: {{ kelas.nama_wali }}</span>
                  <span class="text-xs">{{ kelas.total_siswa }} Siswa</span>
                </div>
              </div>
            </div>
            
            <div class="flex items-center gap-6">
              <div class="text-right">
                <div class="text-xs font-semibold text-slate-400 mb-1">Status</div>
                <div v-if="kelas.total_kurang > 0" class="px-3 py-1 rounded-full bg-red-500/20 text-red-400 text-xs font-bold animate-pulse">
                  {{ kelas.total_kurang }} Siswa Kurang
                </div>
                <div v-else class="px-3 py-1 rounded-full bg-green-500/20 text-green-400 text-xs font-bold">
                  Semua Aman
                </div>
              </div>
              
              <!-- WhatsApp Button -->
              <button 
                v-if="kelas.total_kurang > 0 && kelas.no_hp_wali"
                @click.stop="hubungiWali(kelas)"
                class="px-4 py-2 rounded-xl bg-green-500 hover:bg-green-400 text-black text-sm font-bold shadow-lg shadow-green-500/20 transition-all flex items-center gap-2 hover:scale-105"
              >
                <span>💬</span> Hubungi Wali
              </button>
              
              <div class="text-slate-500 transform transition-transform" :class="{'rotate-180': activeClass === kelas.id_kelas}">▼</div>
            </div>
          </div>

          <!-- Accordion Body: Student List -->
          <div v-show="activeClass === kelas.id_kelas" class="border-t border-white/5 p-5 bg-black/20">
            <div v-if="kelas.students.length === 0" class="text-center py-8 text-slate-500">
              Belum ada data siswa di kelas ini.
            </div>
            <div v-else class="overflow-x-auto">
              <table class="w-full text-left border-collapse">
                <thead>
                  <tr>
                    <th class="px-4 py-3 text-xs font-semibold text-slate-500 uppercase border-b border-white/5 w-16">No</th>
                    <th class="px-4 py-3 text-xs font-semibold text-slate-500 uppercase border-b border-white/5">NIS</th>
                    <th class="px-4 py-3 text-xs font-semibold text-slate-500 uppercase border-b border-white/5">Nama Siswa</th>
                    <th class="px-4 py-3 text-xs font-semibold text-slate-500 uppercase border-b border-white/5 text-center">Rata-rata Nilai</th>
                    <th class="px-4 py-3 text-xs font-semibold text-slate-500 uppercase border-b border-white/5 text-center">Status</th>
                  </tr>
                </thead>
                <tbody>
                  <tr v-for="(siswa, idx) in kelas.students" :key="siswa.id_siswa" class="hover:bg-white/5 transition-colors group">
                    <td class="px-4 py-3 text-sm text-slate-400">{{ idx + 1 }}</td>
                    <td class="px-4 py-3 text-sm font-mono text-slate-300">{{ siswa.nis }}</td>
                    <td class="px-4 py-3 text-sm font-semibold text-white">{{ siswa.nama_siswa }}</td>
                    <td class="px-4 py-3 text-center">
                      <span v-if="siswa.avg_score > 0" class="text-sm font-bold" :class="siswa.status === 'KURANG' ? 'text-red-400' : 'text-green-400'">
                        {{ siswa.avg_score }}
                      </span>
                      <span v-else class="text-xs text-slate-500 italic">Belum ada nilai</span>
                      <div v-if="!siswa.has_report_card && siswa.avg_score > 0" class="text-[9px] text-yellow-500 mt-1">*Nilai Formatif</div>
                    </td>
                    <td class="px-4 py-3 text-center">
                      <span v-if="siswa.status === 'KURANG'" class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded bg-red-500/20 text-red-400 text-xs font-bold">
                        ⚠️ Kurang
                      </span>
                      <span v-else-if="siswa.avg_score > 0" class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded bg-green-500/20 text-green-400 text-xs font-bold">
                        ✅ Aman
                      </span>
                      <span v-else class="text-xs text-slate-600">-</span>
                    </td>
                  </tr>
                </tbody>
              </table>
            </div>
          </div>
          
        </div>
      </div>

    </div>
  </AppLayout>
</template>

<script setup>
import { ref, computed } from 'vue';
import { Head } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';

const props = defineProps({
  classesData: Array
});

const activeClass = ref(null);

const toggleKelas = (id) => {
  activeClass.value = activeClass.value === id ? null : id;
};

const totalSemuaSiswa = computed(() => {
  return props.classesData.reduce((acc, curr) => acc + curr.total_siswa, 0);
});

const totalSemuaKurang = computed(() => {
  return props.classesData.reduce((acc, curr) => acc + curr.total_kurang, 0);
});

const hubungiWali = (kelas) => {
  // Susun daftar siswa yang kurang
  const siswaKurang = kelas.students.filter(s => s.status === 'KURANG');
  
  let msg = `Halo Bapak/Ibu ${kelas.nama_wali}, Wali Kelas ${kelas.nama_kelas}.\n\n`;
  msg += `Kami dari Tim Kurikulum ingin menginformasikan bahwa berdasarkan pantauan sistem LMS, terdapat ${siswaKurang.length} siswa di kelas Bapak/Ibu yang memiliki *rata-rata nilai di bawah KKM (75)*.\n\n`;
  msg += `Mohon bantuan Bapak/Ibu untuk segera menindaklanjuti siswa-siswa berikut:\n`;
  
  siswaKurang.forEach((s, idx) => {
    msg += `${idx + 1}. ${s.nama_siswa} (Rata-rata: ${s.avg_score})\n`;
  });
  
  msg += `\nMohon kerjasamanya agar capaian belajar siswa tersebut dapat diperbaiki sebelum periode rapor berakhir. Terima kasih.`;
  
  const encodedMsg = encodeURIComponent(msg);
  const waUrl = `https://wa.me/${kelas.no_hp_wali}?text=${encodedMsg}`;
  
  window.open(waUrl, '_blank');
};

const navigation = [
  {
    label: 'Utama',
    items: [
      { href: '/admin/dashboard', icon: '📊', label: 'Dashboard' },
    ],
  },
  {
    label: 'Sekolah',
    items: [
      { href: '/admin/wali-kelas', icon: '👤', label: 'Wali Kelas' },
      { href: '/admin/siswa', icon: '👥', label: 'Data Siswa' },
      { href: '/admin/events', icon: '🗓️', label: 'Event & Libur' },
      { href: '/admin/kuesioner', icon: '⭐', label: 'Kuesioner Kinerja' },
    ],
  },
  {
    label: 'Konfigurasi',
    items: [
      { href: '/admin/jadwal-jp', icon: '⏰', label: 'Jadwal JP' },
      { href: '/admin/grade-config', icon: '⚖️', label: 'Bobot Nilai' },
      { href: '/admin/timeline', icon: '📅', label: 'Timeline' },
    ],
  },
  {
    label: 'Laporan',
    items: [
      { href: '/admin/monitoring-nilai', icon: '📈', label: 'Monitoring Nilai' },
      { href: '/admin/rapor', icon: '📋', label: 'Ekspor Rapor' },
      { href: '/admin/laporan-performa', icon: '🏆', label: 'Laporan Performa' },
      { href: '/admin/jurnal', icon: '📓', label: 'Jurnal Tatap Muka' },
    ],
  }
];
</script>
