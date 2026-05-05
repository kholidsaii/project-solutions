<script setup lang="ts">
import { ref, onMounted } from 'vue';
import api from '../../api/axios'; // Sesuaikan path jika perlu

// --- STATE ---
const documents = ref<any[]>([]);
const activities = ref<any[]>([]);
const documentTags = ref<any[]>([]);

const selectedTab = ref('all');
const isUploading = ref(false);

// 🔴 PASTIKAN DUA BARIS INI TERTULIS SEPERTI INI:
const selectedFile = ref<File | null>(null);
const fileInput = ref<HTMLInputElement | null>(null);

const form = ref({
  activity_id: '',
  title: ''
});

// --- FETCH DATA ---
const fetchData = async () => {
  try {
    // Jalankan fetch secara paralel agar lebih cepat
    const [resDocs, resTags, resActivities] = await Promise.all([
      api.get('/project-documents'),
      api.get('/master-data/tags_docs'), // Mengambil tag document dari Master Setup
      api.get('/activities').catch(() => ({ data: [] })) // Opsional: ambil list activity jika ada
    ]);

    documents.value = resDocs.data.data || resDocs.data;
    documentTags.value = resTags.data.data || resTags.data;
    activities.value = resActivities.data.data || resActivities.data;
  } catch (error) {
    console.error("Gagal memuat data:", error);
  }
};

// --- METHODS ---
const clearFile = () => {
  selectedFile.value = null;
  if (fileInput.value) fileInput.value.value = '';
};

const handleFileChange = (e: Event) => {
  const target = e.target as HTMLInputElement;
  if (target.files && target.files.length > 0) {
    const file = target.files[0] as File; 
    
    // Validasi ukuran (Max 20MB)
    if (file.size > 20 * 1024 * 1024) {
      alert('Ukuran file maksimal adalah 20MB!');
      clearFile();
      return;
    }
    
    selectedFile.value = file;
  }
};

const handleUpload = async () => {
  if (!form.value.title || !form.value.activity_id || !selectedFile.value) {
    return alert('Harap isi Judul, pilih Activity, dan lampirkan File!');
  }

  isUploading.value = true;
  const formData = new FormData();
  formData.append('activity_id', form.value.activity_id);
  formData.append('title', form.value.title);
  formData.append('document', selectedFile.value);

  try {
    await api.post('/project-documents', formData, {
      headers: { 'Content-Type': 'multipart/form-data' }
    });
    
    alert('Dokumen berhasil diunggah!');
    
    // Reset Form
    form.value.title = '';
    form.value.activity_id = '';
    clearFile();
    
    // Refresh tabel
    fetchData();
  } catch (error: any) {
    const msg = error.response?.data?.error || error.message;
    alert("Gagal mengunggah dokumen: " + msg);
  } finally {
    isUploading.value = false;
  }
};

const handleDelete = async (id: number) => {
  if (confirm('Yakin ingin menghapus dokumen ini secara permanen?')) {
    try {
      await api.delete(`/project-documents/${id}`);
      alert('Dokumen berhasil dihapus!');
      fetchData();
    } catch (error) {
      alert('Gagal menghapus dokumen.');
    }
  }
};

// --- UTILITIES ---
const formatSize = (bytes: number) => {
  if (!bytes) return '0 B';
  const k = 1024;
  const sizes = ['B', 'KB', 'MB', 'GB'];
  const i = Math.floor(Math.log(bytes) / Math.log(k));
  return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
};

const formatDate = (dateString: string) => {
  if (!dateString) return '-';
  const date = new Date(dateString);
  return date.toLocaleDateString('id-ID', { day: '2-digit', month: 'short', year: 'numeric' });
};

// Menentukan icon berdasarkan ekstensi file
const getFileIcon = (ext: string) => {
  if (!ext) return { icon: 'fas fa-file', color: 'text-slate-400', bg: 'bg-slate-100' };
  const type = ext.toLowerCase();
  if (['pdf'].includes(type)) return { icon: 'fas fa-file-pdf', color: 'text-rose-500', bg: 'bg-rose-50' };
  if (['xls', 'xlsx', 'csv'].includes(type)) return { icon: 'fas fa-file-excel', color: 'text-emerald-500', bg: 'bg-emerald-50' };
  if (['doc', 'docx'].includes(type)) return { icon: 'fas fa-file-word', color: 'text-blue-500', bg: 'bg-blue-50' };
  if (['jpg', 'jpeg', 'png', 'svg'].includes(type)) return { icon: 'fas fa-file-image', color: 'text-amber-500', bg: 'bg-amber-50' };
  if (['zip', 'rar'].includes(type)) return { icon: 'fas fa-file-archive', color: 'text-purple-500', bg: 'bg-purple-50' };
  return { icon: 'fas fa-file-alt', color: 'text-slate-500', bg: 'bg-slate-100' };
};

onMounted(() => {
  fetchData();
});
</script>

<template>
  <div class="grid grid-cols-1 lg:grid-cols-12 gap-6 animate-in fade-in duration-500 pb-20 mt-4">
    
    <!-- SIDEBAR NAVIGATION (Kiri) -->
    <div class="lg:col-span-3 space-y-6">
      <div class="bg-white border border-slate-200 rounded-[2.5rem] overflow-hidden shadow-sm sticky top-8">
        <div class="bg-slate-50/50 border-b border-slate-100 px-6 py-5">
          <span class="text-[11px] font-black text-indigo-900 uppercase tracking-[0.2em]">File Manager</span>
        </div>
        
        <div class="p-4 space-y-6 max-h-[75vh] overflow-y-auto custom-scrollbar">
          
          <!-- Menu Utama -->
          <div>
            <h4 class="text-[9px] font-black uppercase text-slate-400 mb-3 tracking-widest pl-2 flex items-center gap-2">
              <i class="fas fa-folder text-indigo-400"></i> Repositori
            </h4>
            <div class="space-y-1">
              <button @click="selectedTab = 'all'"
                class="w-full flex items-center gap-3 px-4 py-3 text-[10px] font-bold rounded-2xl transition-all uppercase tracking-widest"
                :class="selectedTab === 'all' ? 'bg-indigo-50 text-indigo-600 shadow-sm border border-indigo-100' : 'text-slate-500 hover:bg-slate-50 hover:text-indigo-500 border border-transparent'">
                — Semua Dokumen
              </button>
            </div>
          </div>

          <!-- Document Tags (Terhubung ke Setup Master) -->
          <div class="pt-4 border-t border-slate-100">
            <h4 class="text-[9px] font-black uppercase text-slate-400 mb-3 tracking-widest pl-2 flex items-center gap-2">
              <i class="fas fa-tags text-amber-400"></i> Kategori Label
            </h4>
            <div class="space-y-1">
              <!-- Jika tag kosong -->
              <div v-if="documentTags.length === 0" class="text-[9px] text-slate-400 italic pl-4 font-bold">
                Belum ada tag disetup
              </div>
              <!-- Looping Master Tags Docs -->
              <button v-for="tag in documentTags" :key="tag.id" @click="selectedTab = `tag_${tag.id}`"
                class="w-full flex items-center gap-3 px-4 py-3 text-[10px] font-bold rounded-2xl transition-all uppercase tracking-widest"
                :class="selectedTab === `tag_${tag.id}` ? 'bg-amber-50 text-amber-600 shadow-sm border border-amber-100' : 'text-slate-500 hover:bg-slate-50 hover:text-amber-500 border border-transparent'">
                <i class="fas fa-hashtag text-[8px] opacity-50"></i> {{ tag.name }}
              </button>
            </div>
          </div>

        </div>
      </div>
    </div>

    <!-- MAIN CONTENT (Kanan) -->
    <div class="lg:col-span-9 flex flex-col gap-6">
      
      <!-- Top Bar -->
      <div class="bg-white px-8 py-5 rounded-[2.5rem] border border-slate-200 shadow-sm flex items-center gap-4 text-indigo-900">
        <div class="w-12 h-12 rounded-2xl bg-indigo-50 border border-indigo-100 flex items-center justify-center text-xl shadow-inner text-indigo-600">
          <i class="fas fa-cloud-upload-alt"></i>
        </div>
        <div>
          <h2 class="text-sm font-black text-slate-800 uppercase tracking-widest">Document Center</h2>
          <p class="text-[9px] font-bold text-slate-400 uppercase tracking-widest mt-1">Kelola Berkas & Lampiran Proyek</p>
        </div>
      </div>

      <!-- Form Upload Area -->
      <div class="bg-white p-8 rounded-[3rem] border border-slate-200 shadow-sm transition-all">
        <div class="grid grid-cols-1 lg:grid-cols-12 gap-6 items-end">
          
          <!-- Input Judul Dokumen -->
          <div class="lg:col-span-4 space-y-2">
            <label class="text-[9px] font-black text-slate-400 uppercase tracking-widest ml-1">Judul Dokumen</label>
            <input v-model="form.title" type="text" 
              class="w-full bg-slate-50 border border-slate-100 rounded-2xl px-5 py-4 text-xs font-bold outline-none focus:ring-2 ring-indigo-100 shadow-inner uppercase"
              placeholder="CONTOH: KONTRAK KERJASAMA">
          </div>

          <!-- Dropdown Pilih Activity -->
          <div class="lg:col-span-4 space-y-2">
            <label class="text-[9px] font-black text-slate-400 uppercase tracking-widest ml-1">Terkait Activity</label>
            <div class="relative">
              <select v-model="form.activity_id" class="w-full bg-slate-50 border border-slate-100 rounded-2xl px-5 py-4 text-xs font-bold outline-none uppercase appearance-none cursor-pointer focus:ring-2 ring-indigo-100 shadow-inner">
                <option value="" disabled>-- Pilih Activity --</option>
                <option v-for="act in activities" :key="act.id" :value="act.id">{{ act.title || act.name || `Activity #${act.id}` }}</option>
                <!-- Opsi fallback jika activities kosong, agar tetap bisa dites -->
                <option v-if="activities.length === 0" value="1">Dummy Activity #1</option>
              </select>
              <i class="fas fa-chevron-down absolute right-5 top-1/2 -translate-y-1/2 text-slate-400 pointer-events-none text-xs"></i>
            </div>
          </div>

          <!-- Input File Lampiran -->
          <div class="lg:col-span-4 space-y-2">
            <label class="text-[9px] font-black text-slate-400 uppercase tracking-widest ml-1">Pilih File (Max 20MB)</label>
            <div class="flex gap-2 relative">
              <!-- Custom File Input Display -->
              <div class="flex-1 bg-slate-50 border border-slate-100 rounded-2xl px-4 py-4 text-[10px] font-bold shadow-inner flex items-center overflow-hidden whitespace-nowrap">
                <span v-if="selectedFile" class="text-indigo-600 truncate"><i class="fas fa-check-circle mr-1"></i> {{ selectedFile.name }}</span>
                <span v-else class="text-slate-400 uppercase"><i class="fas fa-paperclip mr-1"></i> Belum ada file</span>
              </div>
              <!-- Tombol Browse -->
              <label class="w-12 h-12 bg-indigo-50 border-2 border-dashed border-indigo-200 rounded-2xl flex items-center justify-center cursor-pointer hover:border-indigo-400 hover:text-indigo-600 text-indigo-400 transition-all flex-none group">
                <input type="file" @change="handleFileChange" class="hidden" ref="fileInput">
                <i class="fas fa-folder-open text-sm group-hover:scale-110 transition-transform"></i>
              </label>
              <!-- Tombol Clear File -->
              <button v-if="selectedFile" @click="clearFile" class="absolute right-[3.5rem] top-1/2 -translate-y-1/2 w-6 h-6 bg-rose-100 text-rose-500 rounded-full flex items-center justify-center text-[10px] hover:bg-rose-500 hover:text-white transition-colors">
                <i class="fas fa-times"></i>
              </button>
            </div>
          </div>

          <!-- Tombol Eksekusi -->
          <div class="lg:col-span-12 flex justify-end pt-2 border-t border-slate-100/60">
            <button @click="handleUpload" :disabled="isUploading" 
              class="bg-indigo-600 text-white px-8 py-4 rounded-2xl text-[10px] font-black uppercase tracking-widest shadow-xl shadow-indigo-200 hover:bg-indigo-700 active:scale-95 transition-all flex items-center gap-3 disabled:opacity-50 disabled:cursor-not-allowed">
              <i class="fas" :class="isUploading ? 'fa-spinner fa-spin' : 'fa-cloud-upload-alt'"></i> 
              {{ isUploading ? 'Mengunggah...' : 'Upload Dokumen' }}
            </button>
          </div>
        </div>
      </div>

      <!-- Data Table Area -->
      <div class="bg-white rounded-[3rem] border border-slate-200 shadow-sm flex-1 overflow-hidden mt-2">
        <div class="overflow-x-auto">
          <table class="w-full text-left min-w-[800px]">
            <thead class="bg-slate-50 border-b border-slate-100">
              <tr class="text-[9px] font-black text-slate-400 uppercase tracking-widest">
                <th class="px-8 py-6 w-16">No</th>
                <th class="px-6 py-6">Informasi Dokumen</th>
                <th class="px-6 py-6">Uploader</th>
                <th class="px-6 py-6">Ukuran</th>
                <th class="px-8 py-6 text-right">Action</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-slate-50">
              <tr v-for="(doc, index) in documents" :key="doc.id" class="hover:bg-slate-50/50 transition-colors group">
                <td class="px-8 py-5 text-[11px] font-black text-slate-400 group-hover:text-indigo-600 transition-colors">#{{ index + 1 }}</td>
                
                <td class="px-6 py-5">
                  <div class="flex items-center gap-4">
                    <!-- Icon Berdasarkan Tipe File -->
                    <div :class="getFileIcon(doc.file_type).bg + ' ' + getFileIcon(doc.file_type).color" 
                         class="w-10 h-10 rounded-xl flex items-center justify-center text-lg shadow-sm border border-white flex-none">
                      <i :class="getFileIcon(doc.file_type).icon"></i>
                    </div>
                    <div>
                      <div class="text-[11px] font-black text-slate-700 uppercase tracking-tight">{{ doc.title }}</div>
                      <div class="flex items-center gap-2 mt-0.5">
                        <span class="text-[9px] font-bold text-slate-400 uppercase tracking-widest border border-slate-200 px-1.5 rounded bg-white">
                          {{ doc.file_type || 'UNKNOWN' }}
                        </span>
                        <span class="text-[9px] font-bold text-slate-400 uppercase tracking-widest">{{ formatDate(doc.created_at) }}</span>
                      </div>
                    </div>
                  </div>
                </td>

                <td class="px-6 py-5">
                  <div class="text-[10px] font-bold text-slate-600 uppercase">{{ doc.uploader_name || 'System / Unknown' }}</div>
                  <div class="text-[8px] font-bold text-slate-400 uppercase tracking-widest mt-0.5">Activity ID: {{ doc.activity_id }}</div>
                </td>

                <td class="px-6 py-5">
                  <div class="text-[10px] font-bold text-slate-500 uppercase">{{ formatSize(doc.file_size) }}</div>
                </td>

                <td class="px-8 py-5 text-right">
                  <div class="flex justify-end gap-3 opacity-0 group-hover:opacity-100 transition-opacity">
                    <!-- Tombol Download (Arahkan ke URL file_path) -->
                    <a :href="doc.file_path.startsWith('http') ? doc.file_path : `http://localhost:8000/storage/${doc.file_path}`" 
                       target="_blank" download
                       class="w-8 h-8 rounded-xl bg-indigo-50 text-indigo-600 hover:bg-indigo-600 hover:text-white flex items-center justify-center transition-all shadow-sm">
                      <i class="fas fa-download text-[10px]"></i>
                    </a>
                    
                    <button @click="handleDelete(doc.id)" class="w-8 h-8 rounded-xl bg-rose-50 text-rose-500 hover:bg-rose-600 hover:text-white flex items-center justify-center transition-all shadow-sm">
                      <i class="fas fa-trash-alt text-[10px]"></i>
                    </button>
                  </div>
                </td>
              </tr>

              <!-- Empty State -->
              <tr v-if="documents.length === 0">
                <td colspan="5" class="px-6 py-16 text-center">
                  <i class="fas fa-folder-open text-4xl text-slate-200 mb-3"></i>
                  <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest">Belum ada dokumen yang diunggah</p>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </div>
</template>

<style scoped>
.custom-scrollbar::-webkit-scrollbar { width: 6px; }
.custom-scrollbar::-webkit-scrollbar-track { background: transparent; }
.custom-scrollbar::-webkit-scrollbar-thumb { background-color: #e2e8f0; border-radius: 10px; }
.custom-scrollbar::-webkit-scrollbar-thumb:hover { background-color: #cbd5e1; }
.animate-in { animation: fadeIn 0.4s ease-out; }
@keyframes fadeIn { from { opacity: 0; transform: translateY(8px); } to { opacity: 1; transform: translateY(0); } }
</style>