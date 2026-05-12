<script setup lang="ts">
import { ref, onMounted } from 'vue';
import api from '../../api/axios'; 

// ==========================================
// 1. STATE MANAGEMENT
// ==========================================
const documents = ref<any[]>([]);
const documentTags = ref<any[]>([]);
const isLoading = ref(false);

// Filter & Search & Pagination State
const activeFilter = ref({ type: 'all', value: 'all' as any });
const searchQuery = ref('');
const openNavMenu = ref<string | null>('tags');

const currentPage = ref(1);
const totalPages = ref(1);

// Modal & Form State
const showUploadModal = ref(false);
const isUploading = ref(false);
const isEditing = ref(false);
const editingId = ref<number | null>(null);

const selectedFile = ref<File | null>(null);
const fileInput = ref<HTMLInputElement | null>(null);

const form = ref({
  title: '',
  tag_id: null as number | null,
  old_file_path: ''
});

// ==========================================
// 2. FETCH DATA (Backend Pagination & Filter)
// ==========================================
const fetchData = async () => {
  isLoading.value = true;
  try {
    // Siapkan parameter untuk dikirim ke backend
    const params: any = { page: currentPage.value };
    
    if (searchQuery.value) params.search = searchQuery.value;
    
    if (activeFilter.value.type === 'tag' && activeFilter.value.value !== 'all') {
      params.tag_id = activeFilter.value.value;
    } else if (activeFilter.value.type === 'ext') {
      params.ext = activeFilter.value.value;
    }

    const [resDocs, resTags] = await Promise.all([
      api.get('/project-documents', { params }),
      api.get('/master-data/tags_docs').catch(() => ({ data: [] }))
    ]);

    // Format Pagination dari Laravel (biasanya di dalam res.data.data)
    documents.value = resDocs.data.data || resDocs.data || [];
    totalPages.value = resDocs.data.last_page || 1;

    documentTags.value = resTags.data.data || resTags.data || [];
  } catch (error) {
    console.error("Gagal sinkronisasi data:", error);
  } finally {
    isLoading.value = false;
  }
};

// ==========================================
// 3. ACTION PAGINATION & FILTER
// ==========================================
const handleSearch = () => {
  currentPage.value = 1;
  fetchData();
};

const setFilter = (type: string, value: any) => {
  activeFilter.value = { type, value };
  currentPage.value = 1;
  fetchData();
};

const nextPage = () => { if (currentPage.value < totalPages.value) { currentPage.value++; fetchData(); } };
const prevPage = () => { if (currentPage.value > 1) { currentPage.value--; fetchData(); } };
const goToPage = (page: number) => { currentPage.value = page; fetchData(); };

// ==========================================
// 4. CRUD ACTIONS
// ==========================================
const handleFileChange = (e: Event) => {
  const target = e.target as HTMLInputElement;
  if (target && target.files && target.files.length > 0) {
    selectedFile.value = target.files[0] as File; 
  } else {
    selectedFile.value = null;
  }
};

const openModal = () => {
  isEditing.value = false;
  editingId.value = null;
  selectedFile.value = null;
  if (fileInput.value) fileInput.value.value = '';
  form.value = { title: '', tag_id: null, old_file_path: '' };
  showUploadModal.value = true;
};

const handleEdit = (doc: any) => {
  isEditing.value = true;
  editingId.value = doc.id;
  selectedFile.value = null;
  if (fileInput.value) fileInput.value.value = '';
  form.value = { 
    title: doc.title, 
    tag_id: doc.tag_id || null,
    old_file_path: doc.file_path || '' 
  };
  showUploadModal.value = true;
};

const handleUpload = async () => {
  if (!form.value.title || (!selectedFile.value && !isEditing.value)) {
    return alert("Harap isi Judul dan Pilih File.");
  }

  const formData = new FormData();
  formData.append('title', form.value.title);
  
  const tagId = activeFilter.value.type === 'tag' ? activeFilter.value.value : form.value.tag_id;
  if (tagId && tagId !== 'all') formData.append('tag_id', String(tagId));

  if (selectedFile.value) formData.append('document', selectedFile.value);

  try {
    isUploading.value = true;
    const url = isEditing.value ? `/project-documents/${editingId.value}` : '/project-documents';
    if (isEditing.value) formData.append('_method', 'PUT');

    await api.post(url, formData, { headers: { 'Content-Type': 'multipart/form-data' }});
    alert(isEditing.value ? "Dokumen diperbarui!" : "Dokumen berhasil diarsipkan!");
    showUploadModal.value = false;
    fetchData();
  } catch (error: any) {
    alert("Gagal memproses file. Periksa koneksi atau database.");
  } finally {
    isUploading.value = false;
  }
};

const handleDelete = async (id: number) => {
  if (!confirm("Hapus dokumen ini secara permanen?")) return;
  try {
    await api.delete(`/project-documents/${id}`);
    fetchData();
  } catch (error) {
    alert("Gagal menghapus.");
  }
};

const handleDownload = (path: string | null | undefined) => {
  if (!path) return alert("File tidak ditemukan.");
  const apiUrl = import.meta.env.VITE_API_URL || 'http://localhost:8000/api';
  window.open(`${apiUrl}/files/download?path=${encodeURIComponent(path)}`, '_blank');
};

// ==========================================
// 5. HELPER (Icon, Ukuran & Nama File Asli)
// ==========================================
const getFileIcon = (type: string) => {
  const t = type?.toLowerCase();
  if (['jpg','jpeg','png','webp'].includes(t)) return 'fas fa-image text-emerald-500';
  if (['mp4','mov','avi'].includes(t)) return 'fas fa-video text-blue-400';
  if (['pdf'].includes(t)) return 'fas fa-file-pdf text-rose-500';
  if (['doc','docx'].includes(t)) return 'fas fa-file-word text-blue-600';
  if (['xls','xlsx'].includes(t)) return 'fas fa-file-excel text-green-600';
  return 'fas fa-file-alt text-slate-400';
};

const formatSize = (bytes: number) => {
  if (!bytes) return '0 B';
  const k = 1024;
  const i = Math.floor(Math.log(bytes) / Math.log(k));
  return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + ['B', 'KB', 'MB', 'GB'][i];
};

const getOriginalFileName = (path: string | null | undefined) => {
  if (!path) return '-';
  const parts = path.split('/');
  const filename = parts.pop(); 
  if (!filename) return '-';
  const nameParts = filename.split('_');
  return nameParts.length > 1 ? nameParts.slice(1).join('_') : filename;
};

onMounted(fetchData);
</script>

<template>
  <div class="grid grid-cols-1 lg:grid-cols-12 gap-6 animate-in fade-in duration-500 pb-20 mt-4">
    
    <div class="lg:col-span-3 space-y-6">
      <div class="bg-white border border-slate-200 rounded-lg p-4 shadow-sm sticky top-8">
        <h4 class="text-[13px] font-bold text-center mb-3 pb-3 border-b border-gray-100 italic uppercase">Navigation</h4>
        
        <div class="space-y-1">
          <button @click="setFilter('all', 'all')" 
            class="w-full flex items-center gap-3 px-3 py-2.5 rounded-lg transition-all text-[12px] font-medium"
            :class="activeFilter.type === 'all' ? 'bg-blue-50 text-blue-600' : 'text-gray-600 hover:bg-gray-50'">
            <div class="w-5 text-center shrink-0"><i class="fas fa-layer-group text-gray-400"></i></div>
            <span>All Documents</span>
          </button>

          <div class="rounded-lg transition-all" :class="openNavMenu === 'photo' ? 'bg-gray-50' : ''">
            <button @click="openNavMenu = openNavMenu === 'photo' ? null : 'photo'" class="w-full flex items-center justify-between px-3 py-2.5 rounded-lg text-[12px] font-medium text-gray-600 hover:bg-gray-100">
              <div class="flex items-center gap-3">
                <div class="w-5 text-center shrink-0"><i class="fas fa-image text-gray-400"></i></div>
                <span>Document Photo</span>
              </div>
              <i class="fas fa-chevron-down text-[10px] transition-transform" :class="openNavMenu === 'photo' ? 'rotate-180' : ''"></i>
            </button>
            <div v-show="openNavMenu === 'photo'" class="px-2 pb-2 space-y-1">
              <button v-for="ext in ['jpg','jpeg','png','webp']" :key="ext" @click="setFilter('ext', ext)" class="w-full text-left py-2 px-11 text-[11px] font-medium rounded-md" :class="activeFilter.value === ext ? 'text-blue-600 bg-blue-100/50' : 'text-gray-500 hover:bg-white'">{{ ext.toUpperCase() }}</button>
            </div>
          </div>

          <div class="rounded-lg transition-all" :class="openNavMenu === 'video' ? 'bg-gray-50' : ''">
            <button @click="openNavMenu = openNavMenu === 'video' ? null : 'video'" class="w-full flex items-center justify-between px-3 py-2.5 rounded-lg text-[12px] font-medium text-gray-600 hover:bg-gray-100">
              <div class="flex items-center gap-3">
                <div class="w-5 text-center shrink-0"><i class="fas fa-video text-gray-400"></i></div>
                <span>Document Video</span>
              </div>
              <i class="fas fa-chevron-down text-[10px] transition-transform" :class="openNavMenu === 'video' ? 'rotate-180' : ''"></i>
            </button>
            <div v-show="openNavMenu === 'video'" class="px-2 pb-2 space-y-1">
              <button v-for="ext in ['mp4','mov','avi']" :key="ext" @click="setFilter('ext', ext)" class="w-full text-left py-2 px-11 text-[11px] font-medium rounded-md" :class="activeFilter.value === ext ? 'text-blue-600 bg-blue-100/50' : 'text-gray-500 hover:bg-white'">{{ ext.toUpperCase() }}</button>
            </div>
          </div>

          <button @click="setFilter('ext', 'pdf')" class="w-full flex items-center gap-3 px-3 py-2.5 rounded-lg text-[12px] font-medium" :class="activeFilter.value === 'pdf' ? 'bg-blue-50 text-blue-600' : 'text-gray-600 hover:bg-gray-50'">
            <div class="w-5 text-center shrink-0"><i class="fas fa-file-pdf text-gray-400"></i></div>
            <span>Document PDF</span>
          </button>

          <div class="rounded-lg transition-all" :class="openNavMenu === 'word' ? 'bg-gray-50' : ''">
            <button @click="openNavMenu = openNavMenu === 'word' ? null : 'word'" class="w-full flex items-center justify-between px-3 py-2.5 rounded-lg text-[12px] font-medium text-gray-600 hover:bg-gray-100">
              <div class="flex items-center gap-3">
                <div class="w-5 text-center shrink-0"><i class="fas fa-file-word text-gray-400"></i></div>
                <span>Document Word</span>
              </div>
              <i class="fas fa-chevron-down text-[10px] transition-transform" :class="openNavMenu === 'word' ? 'rotate-180' : ''"></i>
            </button>
            <div v-show="openNavMenu === 'word'" class="px-2 pb-2 space-y-1">
              <button v-for="ext in ['docx','doc']" :key="ext" @click="setFilter('ext', ext)" class="w-full text-left py-2 px-11 text-[11px] font-medium rounded-md" :class="activeFilter.value === ext ? 'text-blue-600 bg-blue-100/50' : 'text-gray-500 hover:bg-white'">{{ ext.toUpperCase() }}</button>
            </div>
          </div>

          <div class="rounded-lg transition-all" :class="openNavMenu === 'excel' ? 'bg-gray-50' : ''">
            <button @click="openNavMenu = openNavMenu === 'excel' ? null : 'excel'" class="w-full flex items-center justify-between px-3 py-2.5 rounded-lg text-[12px] font-medium text-gray-600 hover:bg-gray-100">
              <div class="flex items-center gap-3">
                <div class="w-5 text-center shrink-0"><i class="fas fa-file-excel text-gray-400"></i></div>
                <span>Document Excel</span>
              </div>
              <i class="fas fa-chevron-down text-[10px] transition-transform" :class="openNavMenu === 'excel' ? 'rotate-180' : ''"></i>
            </button>
            <div v-show="openNavMenu === 'excel'" class="px-2 pb-2 space-y-1">
              <button v-for="ext in ['xlsx','xls']" :key="ext" @click="setFilter('ext', ext)" class="w-full text-left py-2 px-11 text-[11px] font-medium rounded-md" :class="activeFilter.value === ext ? 'text-blue-600 bg-blue-100/50' : 'text-gray-500 hover:bg-white'">{{ ext.toUpperCase() }}</button>
            </div>
          </div>

          <div class="border-t border-gray-100 my-2"></div>

          <div class="rounded-lg transition-all" :class="openNavMenu === 'tags' ? 'bg-gray-50' : ''">
            <button @click="openNavMenu = openNavMenu === 'tags' ? null : 'tags'" class="w-full flex items-center justify-between px-3 py-2.5 rounded-lg text-[12px] font-medium text-gray-600 hover:bg-gray-100">
              <div class="flex items-center gap-3">
                <div class="w-5 text-center shrink-0"><i class="fas fa-tags text-gray-400"></i></div>
                <span>Tags Documents</span>
              </div>
              <i class="fas fa-chevron-down text-[10px] transition-transform" :class="openNavMenu === 'tags' ? 'rotate-180' : ''"></i>
            </button>
            <div v-show="openNavMenu === 'tags'" class="mt-1 px-2 pb-2 space-y-1 max-h-[200px] overflow-y-auto custom-scrollbar">
              <button v-for="tag in documentTags" :key="tag.id" @click="setFilter('tag', tag.id)" class="w-full text-left py-2 px-11 text-[11px] font-medium rounded-md" :class="activeFilter.value === tag.id ? 'text-blue-600 bg-blue-100/50' : 'text-gray-500 hover:bg-white'">{{ tag.name }}</button>
            </div>
          </div>
        </div>
      </div>
    </div>

    <div class="lg:col-span-9 flex flex-col gap-4 relative">
      <div v-if="isLoading" class="absolute inset-0 bg-white/50 backdrop-blur-sm z-50 flex items-center justify-center rounded-xl"><i class="fas fa-spinner fa-spin text-3xl text-[#4361EE]"></i></div>

      <div class="flex flex-col xl:flex-row gap-3 bg-white p-3 border border-gray-200 rounded-lg shadow-sm relative z-10">
        <button class="bg-[#4361EE] text-white px-5 py-2.5 rounded text-sm font-bold flex items-center gap-2 shrink-0">
          <i class="fas fa-folder-open"></i> Global Documents
        </button>
        <div class="flex-1 relative">
          <i class="fas fa-search absolute left-3 top-1/2 -translate-y-1/2 text-gray-400"></i>
          <input v-model="searchQuery" @keyup.enter="handleSearch" type="text" placeholder="Cari judul, nama file, uploader..." class="w-full bg-white border border-gray-300 rounded pl-10 pr-10 py-2.5 text-sm outline-none focus:border-[#4361EE] transition-colors">
          <button v-if="searchQuery" @click="searchQuery = ''; handleSearch()" class="absolute right-3 top-1/2 -translate-y-1/2 text-red-400 hover:text-red-600 transition-colors">
            <i class="fas fa-times"></i>
          </button>
        </div>
        <button @click="openModal" class="bg-[#4361EE] text-white w-10 h-10 rounded flex items-center justify-center font-bold text-lg hover:bg-blue-700 transition-all">
          <i class="fas fa-plus"></i>
        </button>
      </div>

      <div class="bg-white border border-slate-100 rounded-2xl shadow-sm flex flex-col flex-1 overflow-hidden min-h-[500px]">
        <div class="overflow-x-auto flex-1">
          <table class="w-full text-left">
            <thead>
              <tr class="bg-slate-50/50 border-b border-slate-50">
                <th class="px-6 py-4 text-[10px] font-black text-slate-400 uppercase tracking-widest">Document Name</th>
                <th class="px-6 py-4 text-[10px] font-black text-slate-400 uppercase tracking-widest text-center">Type & Size</th>
                <th class="px-6 py-4 text-[10px] font-black text-slate-400 uppercase tracking-widest">Uploaded By</th>
                <th class="px-6 py-4 text-[10px] font-black text-slate-400 uppercase tracking-widest text-center">Action</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-slate-50">
              <tr v-for="doc in documents" :key="doc.id" class="hover:bg-slate-50/30 transition-colors group">
                <td class="px-6 py-4">
                  <div class="flex items-center gap-3">
                    <div class="w-9 h-9 rounded-xl bg-slate-50 flex items-center justify-center border border-slate-100">
                      <i :class="getFileIcon(doc.file_type)" class="text-sm"></i>
                    </div>
                    <div class="flex flex-col">
                       <span class="text-[11px] font-black text-slate-700 uppercase truncate max-w-[250px]">{{ doc.title }}</span>
                       <span class="text-[9px] font-bold text-slate-400 truncate max-w-[250px] mt-0.5" :title="getOriginalFileName(doc.file_path)">
                         <i class="fas fa-paperclip text-slate-300 mr-1"></i> {{ getOriginalFileName(doc.file_path) }}
                       </span>
                    </div>
                  </div>
                </td>
                <td class="px-6 py-4 text-center">
                  <div class="flex flex-col">
                    <span class="text-[10px] font-black text-slate-600 uppercase tracking-tighter">{{ doc.file_type }}</span>
                    <span class="text-[9px] font-bold text-slate-400 italic">{{ formatSize(doc.file_size) }}</span>
                  </div>
                </td>
                <td class="px-6 py-4">
                  <div class="flex items-center gap-2">
                    <div class="w-6 h-6 rounded-full bg-indigo-50 text-indigo-600 flex items-center justify-center text-[9px] font-black border border-indigo-100">
                      {{ (doc.uploader_name || 'U').substring(0,2).toUpperCase() }}
                    </div>
                    <span class="text-[10px] font-bold text-slate-600 capitalize">{{ doc.uploader_name || 'System' }}</span>
                  </div>
                </td>
                <td class="px-6 py-4 text-center">
                  <div class="flex justify-center gap-2">
                    <button @click="handleDownload(doc.file_path)" class="w-7 h-7 rounded bg-blue-50 text-blue-600 hover:bg-blue-600 hover:text-white flex items-center justify-center transition-all shadow-sm"><i class="fas fa-download text-[10px]"></i></button>
                    <button @click="handleEdit(doc)" class="w-7 h-7 rounded bg-amber-50 text-amber-600 hover:bg-amber-600 hover:text-white flex items-center justify-center transition-all shadow-sm"><i class="fas fa-edit text-[10px]"></i></button>
                    <button @click="handleDelete(doc.id)" class="w-7 h-7 rounded bg-rose-50 text-rose-500 hover:bg-rose-600 hover:text-white flex items-center justify-center transition-all shadow-sm"><i class="fas fa-trash-alt text-[10px]"></i></button>
                  </div>
                </td>
              </tr>
              <tr v-if="documents.length === 0 && !isLoading">
                <td colspan="4" class="py-20 text-center">
                  <i class="fas fa-folder-open text-3xl text-slate-200 mb-3"></i>
                  <p class="text-[10px] font-black text-slate-300 uppercase tracking-widest">No Documents Found</p>
                </td>
              </tr>
            </tbody>
          </table>
        </div>

        <div v-if="totalPages > 1" class="mt-auto p-4 border-t border-slate-100 flex flex-col md:flex-row justify-between items-center gap-4 bg-white">
          <p class="text-[10px] font-bold text-gray-400 uppercase tracking-widest">Page {{ currentPage }} of {{ totalPages }}</p>
          <div class="flex items-center gap-1.5">
            <button @click="prevPage" :disabled="currentPage === 1" class="w-7 h-7 flex items-center justify-center rounded bg-white border border-gray-200 text-gray-500 hover:bg-gray-50 disabled:opacity-30 transition-all"><i class="fas fa-chevron-left text-[10px]"></i></button>
            <div class="flex items-center gap-1">
              <button v-for="page in totalPages" :key="page" @click="goToPage(page)" class="w-7 h-7 flex items-center justify-center rounded text-[10px] font-bold transition-all" :class="currentPage === page ? 'bg-[#4361EE] text-white shadow-sm' : 'bg-white border border-gray-200 text-gray-500 hover:bg-gray-50'">{{ page }}</button>
            </div>
            <button @click="nextPage" :disabled="currentPage === totalPages" class="w-7 h-7 flex items-center justify-center rounded bg-white border border-gray-200 text-gray-500 hover:bg-gray-50 disabled:opacity-30 transition-all"><i class="fas fa-chevron-right text-[10px]"></i></button>
          </div>
        </div>
      </div>
    </div>

    <div v-if="showUploadModal" class="fixed inset-0 z-[999] flex items-center justify-center p-4">
      <div class="absolute inset-0 bg-slate-900/60 backdrop-blur-sm" @click="showUploadModal = false"></div>
      <div class="bg-white w-full max-w-md rounded-[2.5rem] shadow-2xl relative z-10 overflow-hidden animate-in zoom-in duration-300">
        <div class="p-8 border-b border-slate-100 flex justify-between items-center bg-slate-50/50">
          <div><h3 class="text-xl font-black text-slate-800 uppercase italic">{{ isEditing ? 'Edit Document' : 'Upload Document' }}</h3></div>
          <button @click="showUploadModal = false" class="text-slate-300 hover:text-rose-500 transition-colors"><i class="fas fa-times-circle text-2xl"></i></button>
        </div>
        <div class="p-8 space-y-5">
          <div class="space-y-1">
            <label class="text-[9px] font-black text-slate-400 uppercase ml-1">Document Title</label>
            <input v-model="form.title" type="text" placeholder="JUDUL DOKUMEN..." class="w-full bg-slate-50 border border-slate-100 rounded-2xl px-5 py-4 text-xs font-bold outline-none focus:ring-2 ring-indigo-100 transition-all uppercase">
          </div>
          <div class="space-y-1">
            <label class="text-[9px] font-black text-slate-400 uppercase ml-1">Select File <span v-if="isEditing" class="text-indigo-400 font-bold lowercase">(Kosongkan jika tidak ganti file)</span></label>
            
            <div v-if="isEditing && form.old_file_path && !selectedFile" class="mb-3 bg-slate-50 border border-slate-200 p-3 rounded-2xl flex items-center justify-between">
              <div class="flex items-center gap-3 overflow-hidden">
                <i class="fas fa-file-alt text-slate-400 text-2xl"></i>
                <div class="flex flex-col truncate">
                  <span class="text-[9px] font-black text-slate-400 uppercase tracking-widest">Current File:</span>
                  <span class="text-[10px] font-bold text-slate-700 truncate" :title="getOriginalFileName(form.old_file_path)">{{ getOriginalFileName(form.old_file_path) }}</span>
                </div>
              </div>
              <button @click.prevent="handleDownload(form.old_file_path)" type="button" class="w-8 h-8 shrink-0 bg-blue-100 text-blue-600 rounded-lg flex items-center justify-center hover:bg-blue-600 hover:text-white transition-all shadow-sm" title="Download Current File"><i class="fas fa-download text-xs"></i></button>
            </div>

            <div @click="fileInput?.click()" class="relative w-full h-[100px] border-2 border-dashed border-slate-200 rounded-2xl flex flex-col items-center justify-center text-slate-400 hover:bg-slate-50 cursor-pointer">
              <input type="file" ref="fileInput" @change="handleFileChange" class="hidden">
              <i class="fas fa-cloud-upload-alt text-2xl mb-2"></i>
              <span class="text-[9px] font-black uppercase text-center px-4 tracking-widest">{{ selectedFile ? selectedFile.name : 'Click to Browse File' }}</span>
            </div>
          </div>
          <button @click="handleUpload" :disabled="isUploading" class="w-full bg-[#4361EE] text-white py-4 rounded-2xl text-[11px] font-black uppercase tracking-[0.2em] shadow-xl hover:bg-blue-700 active:scale-95 transition-all">
            {{ isUploading ? 'Processing...' : (isEditing ? 'Save Changes' : 'Confirm Archive') }}
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<style scoped>
.custom-scrollbar::-webkit-scrollbar { width: 4px; }
.custom-scrollbar::-webkit-scrollbar-thumb { background: #e2e8f0; border-radius: 10px; }
.animate-in { animation: fadeIn 0.4s ease-out; }
@keyframes fadeIn { from { opacity: 0; transform: translateY(8px); } to { opacity: 1; transform: translateY(0); } }
</style>