<script setup lang="ts">
import { ref, onMounted, computed } from 'vue';
import { useRouter } from 'vue-router';
import api from '../api/axios';
import * as XLSX from 'xlsx';
import { jsPDF } from 'jspdf';
import autoTable from 'jspdf-autotable'; 

const router = useRouter();

interface ProjectReport {
  id: string;
  client_name: string; 
  category: string; 
  score: number;
  status: string; 
  date: string;
}

const reports = ref<ProjectReport[]>([]);
const searchQuery = ref('');
const selectedCategory = ref('');
const isLoading = ref(true);

const fetchReports = async () => {
  try {
    isLoading.value = true;
    const res = await api.get('/reports/all');
    reports.value = res.data;
  } catch (e) {
    console.error("Gagal ambil laporan project:", e);
    // Data dummy untuk jaga-jaga jika API belum siap
    reports.value = [
      { id: '1', client_name: 'PT Maju Jaya', category: 'WEB', score: 85, status: 'ON TRACK', date: '22/04/2026' },
      { id: '2', client_name: 'Bank Central', category: 'APPS', score: 40, status: 'DELAYED', date: '20/04/2026' }
    ];
  } finally {
    isLoading.value = false;
  }
};

const downloadPDF = (report: ProjectReport) => {
  try {
    const doc = new jsPDF('p', 'mm', 'a4');
    const pageWidth = doc.internal.pageSize.getWidth();

    // Custom Blue Branding for Project Solutions
    doc.setFillColor(30, 58, 138); // Indigo-900
    doc.rect(0, 0, pageWidth, 45, 'F');
    
    doc.setTextColor(255, 255, 255);
    doc.setFont('helvetica', 'bold');
    doc.setFontSize(22);
    doc.text('PROJECT REPORT', 20, 20);
    
    doc.setFontSize(10);
    doc.setFont('helvetica', 'normal');
    doc.text('PROJECT SOLUTIONS - EXECUTION & MONITORING SYSTEM', 20, 28);
    doc.text(`Report ID: #${report.id.padStart(5, '0')}`, 20, 34);

    // Main Table Assessment
    autoTable(doc, {
      startY: 55,
      margin: { left: 20, right: 20 },
      head: [['PARAMETER', 'STATUS / DESCRIPTION']],
      body: [
        ['Client Organization', report.client_name],
        ['Service Category', report.category],
        ['Completion Progress', `${report.score}%`],
        ['Project Status', report.status],
        ['Assessment Date', report.date],
      ],
      headStyles: { fillColor: [79, 70, 229], fontStyle: 'bold', fontSize: 11 },
      styles: { font: 'helvetica', fontSize: 10, cellPadding: 6 },
      theme: 'grid'
    });

    const finalY = (doc as any).lastAutoTable.finalY || 130;
    
    // Signatures
    doc.setTextColor(100, 116, 139);
    doc.setFontSize(9);
    doc.text('This document is electronically generated and valid without signature.', 20, finalY + 10);

    doc.setTextColor(15, 23, 42);
    doc.setFont('helvetica', 'bold');
    doc.text('Project Manager,', 20, finalY + 30);
    doc.text('Client Representative,', pageWidth - 70, finalY + 30);
    
    doc.setDrawColor(203, 213, 225);
    doc.line(20, finalY + 55, 70, finalY + 55); 
    doc.line(pageWidth - 70, finalY + 55, pageWidth - 20, finalY + 55);

    doc.save(`Solutions_Report_${report.client_name.replace(/\s+/g, '_')}.pdf`);
  } catch (error) {
    console.error("PDF Error:", error);
    alert("Terjadi kesalahan saat membuat PDF.");
  }
};

const exportData = () => {
  if (filteredReports.value.length === 0) return alert("Data tidak tersedia!");
  const dataToExport = filteredReports.value.map(r => ({
    "CLIENT NAME": r.client_name,
    "CATEGORY": r.category,
    "PROGRESS (%)": r.score,
    "STATUS": r.status,
    "REPORT DATE": r.date
  }));
  const worksheet = XLSX.utils.json_to_sheet(dataToExport);
  const workbook = XLSX.utils.book_new();
  XLSX.utils.book_append_sheet(workbook, worksheet, "Solutions_Rekap");
  XLSX.writeFile(workbook, `Solutions_Data_Export_${new Date().toISOString().slice(0,10)}.xlsx`);
};

const filteredReports = computed(() => {
  return reports.value.filter(r => {
    // Tambahkan pengecekan r.client_name ?? '' agar jika null jadi string kosong
    const name = r.client_name ? r.client_name.toLowerCase() : '';
    const query = searchQuery.value ? searchQuery.value.toLowerCase() : '';
    
    const matchSearch = name.includes(query);
    
    const matchCat = selectedCategory.value 
      ? (r.category && r.category.toUpperCase() === selectedCategory.value.toUpperCase()) 
      : true;

    return matchSearch && matchCat;
  });
});

const getStatusClass = (status: string) => {
  const base = 'px-4 py-1.5 rounded-full text-[9px] font-black uppercase tracking-wider shadow-sm border-2 ';
  const s = status.toUpperCase();
  if (s === 'STABLE' || s === 'ON TRACK' || s === 'COMPLETED') return base + 'bg-emerald-50 text-emerald-600 border-emerald-100';
  if (s === 'MAINTENANCE' || s === 'PLANNING') return base + 'bg-blue-50 text-blue-600 border-blue-100';
  if (s === 'DELAYED' || s === 'PENDING') return base + 'bg-amber-50 text-amber-600 border-amber-100';
  return base + 'bg-rose-50 text-rose-600 border-rose-100';
};

onMounted(fetchReports);
</script>

<template>
  <div class="w-full min-h-screen bg-slate-50 p-4 md:p-10 pb-32 overflow-x-hidden">
    
    <header class="max-w-7xl mx-auto mb-10 flex flex-col lg:flex-row justify-between items-start lg:items-end gap-6">
      <div class="text-left">
        <div class="flex items-center gap-3 mb-2">
            <div class="w-2 h-8 bg-brand rounded-full"></div>
            <h1 class="text-2xl md:text-3xl font-black text-slate-900 italic uppercase leading-none">
              Project <span class="text-brand">Reports</span>
            </h1>
        </div>
        <p class="text-[10px] md:text-xs text-slate-400 font-bold italic uppercase tracking-[0.15em] ml-5">
          Solutions Performance & Tracking System
        </p>
      </div>

      <div class="flex flex-col sm:flex-row gap-3 w-full lg:w-auto">
        <div class="relative flex-1 sm:w-64">
          <i class="fas fa-search absolute left-4 top-1/2 -translate-y-1/2 text-slate-300"></i>
          <input v-model="searchQuery" type="text" placeholder="Filter by Client..." 
            class="w-full pl-11 pr-4 py-3.5 bg-white border-2 border-slate-100 rounded-2xl outline-none focus:border-brand font-bold shadow-sm text-sm transition-all" />
        </div>
        
        <select v-model="selectedCategory" 
          class="bg-white border-2 border-slate-100 px-4 py-3.5 rounded-2xl font-black text-[10px] uppercase shadow-sm outline-none focus:border-brand cursor-pointer">
          <option value="">All Categories</option>
          <option value="WEB">WEB DEVELOPMENT</option>
          <option value="APPS">MOBILE APPS</option>
          <option value="INFRA">INFRASTRUCTURE</option>
          <option value="MAINTENANCE">MAINTENANCE</option>
          <option value="CONSULTING">CONSULTING</option>
        </select>

        <button @click="exportData" 
          class="px-8 py-3.5 bg-indigo-600 text-white rounded-2xl font-black shadow-lg hover:bg-indigo-700 active:scale-95 transition-all flex items-center justify-center gap-3 uppercase text-[10px] tracking-widest">
          <i class="fas fa-file-excel text-sm"></i> EXPORT EXCEL
        </button>
      </div>
    </header>

    <div class="max-w-7xl mx-auto">
      <div class="bg-white rounded-[2.5rem] shadow-xl shadow-slate-200/40 overflow-hidden border border-slate-100 transition-all">
        
        <div v-if="isLoading" class="p-32 text-center">
            <div class="inline-block w-12 h-12 border-4 border-slate-100 border-t-brand rounded-full animate-spin mb-4"></div>
            <p class="font-black text-slate-300 italic uppercase text-xs tracking-widest">Generating solution reports...</p>
        </div>

        <div v-else class="overflow-x-auto scrollbar-hide">
          <table class="w-full text-left border-collapse min-w-[800px]">
            <thead>
              <tr class="bg-slate-900 text-white text-[9px] md:text-[10px] font-black uppercase tracking-[0.2em] italic">
                <th class="px-8 py-7">Client / Execution Date</th>
                <th class="px-8 py-7 text-center">Project Category</th>
                <th class="px-8 py-7 text-center">Health Score</th>
                <th class="px-8 py-7 text-center">Status</th>
                <th class="px-8 py-7 text-center">Management</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-slate-50 font-bold italic">
              <tr v-for="report in filteredReports" :key="report.id" class="hover:bg-indigo-50/40 transition-all group cursor-default">
                <td class="px-8 py-6">
                  <div class="text-sm font-black text-slate-800 uppercase leading-tight group-hover:text-brand transition-colors">{{ report.client_name }}</div>
                  <div class="text-[9px] text-slate-400 uppercase tracking-widest mt-1 font-bold">{{ report.date }}</div>
                </td>
                <td class="px-8 py-6 text-center">
                  <span class="px-4 py-1.5 bg-slate-100 text-slate-500 rounded-xl text-[9px] font-black uppercase tracking-tighter group-hover:bg-brand/10 group-hover:text-brand transition-all">
                    {{ report.category }}
                  </span>
                </td>
                <td class="px-8 py-6 text-center">
                    <div class="flex flex-col items-center">
                        <span class="font-black text-indigo-600 text-xl">{{ report.score }}%</span>
                        <div class="w-16 h-1 bg-slate-100 rounded-full mt-1 overflow-hidden">
                            <div class="h-full bg-brand" :style="{ width: report.score + '%' }"></div>
                        </div>
                    </div>
                </td>
                <td class="px-8 py-6 text-center">
                  <span :class="getStatusClass(report.status)">{{ report.status }}</span>
                </td>
                <td class="px-8 py-6 text-center">
                  <div class="flex justify-center gap-3">
                    <button class="w-11 h-11 bg-slate-50 text-slate-400 hover:bg-brand hover:text-white rounded-2xl flex items-center justify-center transition-all shadow-sm">
                      <i class="fas fa-search-plus text-xs"></i>
                    </button>
                    <button @click="downloadPDF(report)" 
                      class="w-11 h-11 bg-emerald-50 text-emerald-600 hover:bg-emerald-600 hover:text-white rounded-2xl flex items-center justify-center transition-all shadow-sm">
                      <i class="fas fa-file-pdf text-xs"></i>
                    </button>
                  </div>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>

      <div v-if="!isLoading && filteredReports.length === 0" class="mt-8 bg-white p-20 rounded-[2.5rem] border-2 border-dashed border-slate-200 text-center">
          <i class="fas fa-box-open text-5xl text-slate-100 mb-5"></i>
          <h4 class="text-slate-400 font-black italic uppercase text-xs tracking-[0.2em]">No solution reports match your filter</h4>
      </div>
    </div>
  </div>
</template>

<style scoped>
.scrollbar-hide::-webkit-scrollbar { display: none; }
.scrollbar-hide { -ms-overflow-style: none; scrollbar-width: none; }
</style>