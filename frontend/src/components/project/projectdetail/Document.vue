<script setup lang="ts">
import { computed } from 'vue';

// Mengambil data project dari parent (ProjectDetail.vue)
const props = defineProps(['project']);

// Menarik data documents bawaan dari query project
const documents = computed(() => props.project?.documents || []);

// ==========================================
// HELPER FORMATTING & DOWNLOAD
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

const handleDownload = (path: string | null | undefined) => {
  if (!path) return alert("File tidak ditemukan.");
  const apiUrl = import.meta.env.VITE_API_URL || 'http://localhost:8000/api';
  window.open(`${apiUrl}/files/download?path=${encodeURIComponent(path)}`, '_blank');
};
</script>

<template>
  <div class="animate-in fade-in duration-500 space-y-6 w-full">
    
    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4 bg-white p-4 rounded-2xl shadow-sm border border-slate-200 w-full">
      <div class="flex items-center gap-4 min-w-0">
        <div class="w-10 h-10 shrink-0 bg-indigo-50 text-indigo-600 rounded-xl flex items-center justify-center">
          <i class="fas fa-folder-open"></i>
        </div>
        <div class="min-w-0">
          <h3 class="text-sm font-black text-slate-800 uppercase tracking-widest truncate">Project Documents</h3>
          <p class="text-[10px] text-slate-500 font-bold uppercase mt-0.5 truncate">Arsip dan Lampiran (ID-{{ project?.id }})</p>
        </div>
      </div>
      <span class="bg-slate-100 text-slate-500 px-4 py-2 rounded-xl text-[10px] font-black uppercase whitespace-nowrap flex items-center gap-2 shrink-0">
        <i class="fas fa-lock"></i> Read Only
      </span>
    </div>

    <div class="bg-white border border-slate-200 rounded-2xl shadow-sm w-full overflow-hidden min-h-[400px]">
      <div class="overflow-x-auto w-full">
        <table class="w-full text-left min-w-[700px]">
          <thead class="bg-slate-50 border-b border-slate-100">
            <tr>
              <th class="px-6 py-4 text-[10px] font-black text-slate-400 uppercase tracking-widest whitespace-nowrap">Document Name</th>
              <th class="px-6 py-4 text-[10px] font-black text-slate-400 uppercase tracking-widest text-center whitespace-nowrap">Type & Size</th>
              <th class="px-6 py-4 text-[10px] font-black text-slate-400 uppercase tracking-widest whitespace-nowrap">Uploaded By</th>
              <th class="px-6 py-4 text-[10px] font-black text-slate-400 uppercase tracking-widest text-center whitespace-nowrap">Download</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-slate-50">
            <tr v-for="doc in documents" :key="doc.id" class="hover:bg-slate-50/50 transition-colors group">
              <td class="px-6 py-4">
                <div class="flex items-center gap-4 min-w-0">
                  <div class="w-10 h-10 shrink-0 rounded-xl bg-white flex items-center justify-center border border-slate-100 shadow-sm">
                    <i :class="getFileIcon(doc.file_type)" class="text-lg"></i>
                  </div>
                  <div class="flex flex-col min-w-0">
                     <span class="text-[11px] font-black text-slate-700 uppercase truncate max-w-[250px] sm:max-w-[300px]">{{ doc.title }}</span>
                     <span class="text-[9px] font-bold text-slate-400 truncate max-w-[250px] sm:max-w-[300px] mt-0.5" :title="getOriginalFileName(doc.file_path)">
                       <i class="fas fa-paperclip text-slate-300 mr-1"></i> {{ getOriginalFileName(doc.file_path) }}
                     </span>
                  </div>
                </div>
              </td>
              <td class="px-6 py-4 text-center whitespace-nowrap">
                <div class="flex flex-col">
                  <span class="text-[10px] font-black text-slate-600 uppercase tracking-tighter">{{ doc.file_type }}</span>
                  <span class="text-[9px] font-bold text-slate-400 italic">{{ formatSize(doc.file_size) }}</span>
                </div>
              </td>
              <td class="px-6 py-4 whitespace-nowrap">
                <div class="flex items-center gap-2">
                  <div class="w-7 h-7 shrink-0 rounded-full bg-indigo-50 text-indigo-600 flex items-center justify-center text-[10px] font-black border border-indigo-100">
                    {{ (doc.uploader_name || 'U').substring(0,2).toUpperCase() }}
                  </div>
                  <span class="text-[10px] font-bold text-slate-600 capitalize truncate max-w-[120px]">{{ doc.uploader_name || 'System' }}</span>
                </div>
              </td>
              <td class="px-6 py-4 text-center">
                <button @click="handleDownload(doc.file_path)" class="w-8 h-8 rounded-lg bg-blue-50 text-blue-600 hover:bg-blue-600 hover:text-white flex items-center justify-center transition-all shadow-sm mx-auto">
                  <i class="fas fa-download text-[11px]"></i>
                </button>
              </td>
            </tr>

            <tr v-if="documents.length === 0">
              <td colspan="4" class="py-24 text-center">
                <i class="fas fa-folder-open text-4xl text-slate-200 mb-4"></i>
                <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest">Belum Ada Dokumen Terhubung</p>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>

  </div>
</template>