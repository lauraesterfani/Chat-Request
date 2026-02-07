"use client";

import { useState, useEffect } from "react";
import axios from "axios";
import Link from "next/link";
import { UserPlus, Users, FileText, LayoutDashboard, AlertTriangle, Clock, Loader2, FileType } from "lucide-react";
import { useRouter } from "next/navigation";


const API_BASE = "http://127.0.0.1:8000/api";

export default function DashboardPage() {
  const router = useRouter();
  const [loading, setLoading] = useState(true);
  const [stats, setStats] = useState({ 
    total_requerimentos: 0, 
    total_usuarios: 0,
    pendentes: 0,
    em_analise: 0,
    atrasados: 0 
  });

  const fetchStats = async () => {
    try {
      // 1. Pega o token salvo
      const token = localStorage.getItem("jwt_token");

      // 2. Se não tiver token, manda pro login
      if (!token) {
        router.push("/login");
        return;
      }

      // 3. Busca os dados na API (com cabeçalho de Autorização)
      const res = await axios.get(`${API_BASE}/dashboard/estatisticas`, {
        headers: { 
          Authorization: `Bearer ${token}`,
          Accept: 'application/json'
        },
      });

      console.log("Estatísticas recebidas:", res.data); // Debug no Console
      setStats(res.data);
      setLoading(false);

    } catch (error: any) {
      console.error("Erro ao buscar estatísticas:", error);
      setLoading(false);
      
      // Se der erro de autorização (401), força logout
      if (error.response?.status === 401) {
        localStorage.removeItem("jwt_token");
        router.push("/login");
      }
    }
  };

  useEffect(() => {
    fetchStats();
  }, []);

  if (loading) {
    return (
      <div className="min-h-screen flex items-center justify-center bg-[#F4F6F8]">
        <div className="flex flex-col items-center gap-2 text-blue-900">
           <Loader2 className="animate-spin" size={40} />
           <p className="font-bold">Carregando painel...</p>
        </div>
      </div>
    );
  }

  return (
    <div className="min-h-screen flex flex-col md:flex-row bg-[#F4F6F8]">
      
      {/* Sidebar */}
     <aside className="hidden md:flex w-64 bg-[#0B0D3A] text-white flex-col p-6 shadow-lg shrink-0">
        <h1 className="text-2xl font-bold mb-4 flex items-center gap-2 truncate">
           <LayoutDashboard size={24} /> Chat Request
        </h1>
        <p className="text-xs opacity-60 mb-8 uppercase tracking-widest">Painel Administrativo</p>
        
        <nav className="space-y-2 sticky top-6"> 
           {/* Link Visão Geral */}
           <Link href="/dashboard/admin" className="flex items-center gap-3 px-4 py-3 bg-white/10 rounded-xl text-blue-100 font-medium">
             <LayoutDashboard size={18} /> 
             <span>Visão Geral</span>
           </Link>

           {/* Link Todos Pedidos */}
           <Link href="/requests" className="flex items-center gap-3 px-4 py-3 hover:bg-white/5 rounded-xl text-gray-300 transition-colors">
             <FileText size={18} /> 
             <span>Todos Pedidos</span>
           </Link>

           {/* Link Tipos de Requerimento (NOVO - AGORA DENTRO DO NAV) */}
           <Link href="/dashboard/admin/types" className="flex items-center gap-3 px-4 py-3 hover:bg-white/5 rounded-xl text-gray-300 transition-colors">
             <FileType size={18} /> 
             <span>Tipos de Requerimento</span>
           </Link>

                    <Link href="/dashboard/admin/staff" className="flex items-center gap-3 px-4 py-3 hover:bg-white/5 rounded-xl text-gray-300 transition-colors">
            <Users size={18} /> 
            <span>Equipe CRADT</span>
              </Link>
        </nav>
      </aside>
      
      {/* Conteúdo Principal */}
      <main className="flex-1 p-6 md:p-10 w-full min-w-0">
        <div className="max-w-7xl mx-auto space-y-8">
          
          <div className="flex justify-between items-end mb-8">
            <div>
              <h2 className="text-3xl font-bold text-[#0B0D3A]">Visão Geral</h2>
              <p className="text-gray-500">Acompanhamento da equipe CRADT.</p>
            </div>
            <div className="text-right hidden sm:block">
              <p className="text-sm font-bold text-gray-400 uppercase tracking-widest">Hoje</p>
              <p className="text-xl font-bold text-slate-700">{new Date().toLocaleDateString('pt-BR')}</p>
            </div>
          </div>

          {/* --- ALERTA DE ATRASO (Só aparece se tiver > 0) --- */}
          {stats.atrasados > 0 && (
            <div className="bg-red-50 border-l-4 border-red-500 p-6 rounded-r-xl shadow-sm flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4 mb-6 animate-pulse">
              <div className="flex items-center gap-4">
                <div className="bg-red-100 p-3 rounded-full text-red-600">
                  <AlertTriangle size={32} />
                </div>
                <div>
                  <h3 className="text-lg font-bold text-red-800">Atenção: Prazos Vencidos!</h3>
                  <p className="text-red-600 text-sm">
                    Existem <strong className="text-lg mx-1">{stats.atrasados}</strong> requerimentos pendentes há mais de 5 dias.
                  </p>
                </div>
              </div>
              <Link href="/requests" className="whitespace-nowrap px-5 py-2.5 bg-red-600 text-white text-sm font-bold rounded-lg hover:bg-red-700 shadow-md transition-all flex items-center gap-2">
                <Clock size={16} /> Ver Atrasados
              </Link>
            </div>
          )}

          {/* Cards Principais */}
          <div className="grid grid-cols-1 md:grid-cols-3 gap-6">
            
            {/* Card Total */}
            <Link href="/requests" className="bg-white p-6 rounded-3xl shadow-sm border border-gray-100 flex items-center gap-4 hover:border-blue-400 hover:shadow-lg transition-all cursor-pointer">
               <div className="w-14 h-14 bg-blue-50 text-blue-600 rounded-2xl flex items-center justify-center">
                 <FileText size={28} />
               </div>
               <div>
                 <p className="text-xs text-gray-500 font-medium uppercase tracking-wider">Total Recebido</p>
                 <h3 className="text-3xl font-bold text-slate-800">{stats.total_requerimentos}</h3>
               </div>
            </Link>

            {/* Card Pendentes (Novo) */}
            <div className="bg-white p-6 rounded-3xl shadow-sm border border-gray-100 flex items-center gap-4">
               <div className="w-14 h-14 bg-amber-50 text-amber-600 rounded-2xl flex items-center justify-center">
                 <Clock size={28} />
               </div>
               <div>
                 <p className="text-xs text-gray-500 font-medium uppercase tracking-wider">Pendentes</p>
                 <h3 className="text-3xl font-bold text-slate-800">{stats.pendentes}</h3>
               </div>
            </div>

            {/* Card Staff */}
            <Link href="/dashboard/admin/staff" className="bg-white p-6 rounded-3xl shadow-sm border border-gray-100 flex items-center gap-4 hover:border-purple-400 hover:shadow-lg transition-all cursor-pointer">
               <div className="w-14 h-14 bg-purple-50 text-purple-600 rounded-2xl flex items-center justify-center">
                 <Users size={28} />
               </div>
               <div>
                 <h3 className="text-lg font-bold text-slate-800">Equipe</h3>
                 <p className="text-xs text-gray-400">Ver administradores</p>
               </div>
            </Link>
          </div>

        </div>
      </main>
    </div>
  );
}