"use client";

import { useState, useEffect } from "react";
import axios from "axios";
import Link from "next/link";
import { 
  Users, FileText, LayoutDashboard, 
  AlertTriangle, Clock, Loader2, 
  FileType, ShieldCheck, X 
} from "lucide-react";
import { useRouter } from "next/navigation";

const API_BASE = "/api";

export default function DashboardPage() {
  const router = useRouter();
  const [loading, setLoading] = useState(true);
  const [userRole, setUserRole] = useState("");
  const [admins, setAdmins] = useState([]);
  const [isModalOpen, setIsModalOpen] = useState(false);
  const [stats, setStats] = useState({
    total_requerimentos: 0,
    total_usuarios: 0,
    pendentes: 0,
    em_analise: 0,
    atrasados: 0
  });

  const fetchAdmins = async () => {
    try {
      const token = localStorage.getItem("jwt_token");
      const res = await axios.get(`${API_BASE}/admins`, {
        headers: { Authorization: `Bearer ${token}` }
      });
      setAdmins(res.data);
    } catch (error) {
      console.error("Erro ao buscar admins:", error);
    }
  };

  const handleOpenModal = async () => {
    await fetchAdmins();
    setIsModalOpen(true);
  };

  useEffect(() => {
    const init = async () => {
      const token = localStorage.getItem("jwt_token");
      if (!token) {
        router.push("/login");
        return;
      }

      try {
        const meRes = await axios.get(`${API_BASE}/me`, {
          headers: { Authorization: `Bearer ${token}` }
        });
        const role = meRes.data.role;
        setUserRole(role);

        if (role === 'admin') {
          const statsRes = await axios.get(`${API_BASE}/dashboard/estatisticas`, {
            headers: { Authorization: `Bearer ${token}` }
          });
          setStats(statsRes.data);
        }
      } catch (error: any) {
        console.error("Erro ao carregar dashboard:", error);
        if (error.response?.status === 401) {
          localStorage.removeItem("jwt_token");
          router.push("/login");
        }
      } finally {
        setLoading(false);
      }
    };

    init();
  }, [router]);

  if (loading) {
    return (
      <div className="min-h-screen flex items-center justify-center bg-[#F4F6F8]">
        <div className="flex flex-col items-center gap-2 text-emerald-600">
          <Loader2 className="animate-spin" size={40} />
          <p className="font-bold">Carregando painel...</p>
        </div>
      </div>
    );
  }

  return (
    <div className="min-h-screen flex flex-col md:flex-row bg-[#F4F6F8]">
      
      {/* Sidebar */}
      <aside className="hidden md:flex w-64 bg-[#004d40] text-white flex-col p-6 shadow-lg shrink-0">
        <h1 className="text-2xl font-bold mb-4 flex items-center gap-2 truncate">
          <LayoutDashboard size={24} /> Chat Request
        </h1>
        <p className="text-xs opacity-60 mb-8 uppercase tracking-widest">Painel Administrativo</p>
        
        <nav className="space-y-2 sticky top-6">
          {userRole === 'admin' && (
            <>
              <Link href="/dashboard/admin" className="flex items-center gap-3 px-4 py-3 bg-white/10 rounded-xl text-emerald-100 font-medium">
                <LayoutDashboard size={18} />
                <span>Visão Geral</span>
              </Link>
              <Link href="/requests" className="flex items-center gap-3 px-4 py-3 hover:bg-white/5 rounded-xl text-gray-300 transition-colors">
                <FileText size={18} />
                <span>Todos Pedidos</span>
              </Link>
            </>
          )}

          {userRole === 'staff' && (
            <>
              <div className="pt-4 pb-2">
                <p className="text-xs opacity-40 uppercase tracking-widest font-bold">Configurações</p>
              </div>
              <Link href="/dashboard/admin/types" className="flex items-center gap-3 px-4 py-3 hover:bg-white/5 rounded-xl text-gray-300 transition-colors">
                <FileType size={18} />
                <span>Tipos de Requerimento</span>
              </Link>
              <Link href="/dashboard/admin/staff" className="flex items-center gap-3 px-4 py-3 hover:bg-white/5 rounded-xl text-gray-300 transition-colors">
                <Users size={18} />
                <span>Gestão de Acessos</span>
              </Link>
            </>
          )}
        </nav>
      </aside>

      {/* Conteúdo Principal */}
      <main className="flex-1 p-6 md:p-10 w-full min-w-0">
        
        {userRole === 'staff' ? (
          /* --- VISÃO DO STAFF (TI) --- */
          <div className="max-w-4xl mx-auto mt-10">
            <div className="bg-white rounded-3xl p-10 shadow-sm border border-gray-100 text-center">
              <div className="w-20 h-20 bg-emerald-100 text-emerald-600 rounded-full flex items-center justify-center mx-auto mb-6">
                <ShieldCheck size={40} />
              </div>
              <h2 className="text-3xl font-bold text-[#000000] mb-4">Painel Técnico (TI)</h2>
              <p className="text-gray-500 max-w-lg mx-auto mb-10">
                Bem-vindo à área de configuração. Utilize os atalhos abaixo para gerenciar as definições do sistema e permissões de acesso.
              </p>
              <div className="grid grid-cols-1 md:grid-cols-2 gap-6 max-w-2xl mx-auto">
                <Link href="/dashboard/admin/types" className="group p-6 border border-gray-100 rounded-2xl hover:border-emerald-500 hover:shadow-lg transition-all text-left">
                  <div className="flex items-center justify-between mb-4">
                    <span className="p-3 bg-emerald-50 text-emerald-600 rounded-xl group-hover:bg-emerald-600 group-hover:text-white transition-colors">
                      <FileType size={24} />
                    </span>
                  </div>
                  <h3 className="text-lg font-bold text-gray-800">Tipos de Requerimento</h3>
                  <p className="text-sm text-gray-400 mt-1">Criar e editar opções de solicitação.</p>
                </Link>
                <Link href="/dashboard/admin/staff" className="group p-6 border border-gray-100 rounded-2xl hover:border-emerald-500 hover:shadow-lg transition-all text-left">
                  <div className="flex items-center justify-between mb-4">
                    <span className="p-3 bg-emerald-50 text-emerald-600 rounded-xl group-hover:bg-emerald-600 group-hover:text-white transition-colors">
                      <Users size={24} />
                    </span>
                  </div>
                  <h3 className="text-lg font-bold text-gray-800">Gestão de Equipe</h3>
                  <p className="text-sm text-gray-400 mt-1">Adicionar admins e staff.</p>
                </Link>
              </div>
            </div>
          </div>
        ) : (
          /* --- VISÃO DA CRADT (ADMIN) --- */
          <div className="max-w-7xl mx-auto space-y-8">
            <div className="flex justify-between items-end mb-8">
              <div>
                <h2 className="text-3xl font-bold text-[#000000]">Visão Geral</h2>
                <p className="text-gray-500">Acompanhamento da equipe CRADT.</p>
              </div>
              <div className="text-right hidden sm:block">
                <p className="text-sm font-bold text-gray-400 uppercase tracking-widest">Hoje</p>
                <p className="text-xl font-bold text-slate-700">{new Date().toLocaleDateString('pt-BR')}</p>
              </div>
            </div>

            {stats.atrasados > 0 && (
              <div className="bg-red-50 border-l-4 border-red-500 p-6 rounded-r-xl shadow-sm flex flex-col sm:flex-row items-center justify-between gap-4 mb-6 animate-pulse">
                <div className="flex items-center gap-4">
                  <div className="bg-red-100 p-3 rounded-full text-red-600">
                    <AlertTriangle size={32} />
                  </div>
                  <div>
                    <h3 className="text-lg font-bold text-red-800">Atenção: Prazos Vencidos!</h3>
                    <p className="text-red-600 text-sm">Existem <strong className="text-lg mx-1">{stats.atrasados}</strong> requerimentos pendentes há mais de 5 dias.</p>
                  </div>
                </div>
                <Link href="/requests" className="whitespace-nowrap px-5 py-2.5 bg-red-600 text-white text-sm font-bold rounded-lg hover:bg-red-700 shadow-md transition-all flex items-center gap-2">
                  <Clock size={16} /> Ver Atrasados
                </Link>
              </div>
            )}

            <div className="grid grid-cols-1 md:grid-cols-3 gap-6">
              <Link href="/requests" className="bg-white p-6 rounded-3xl shadow-sm border border-gray-100 flex items-center gap-4 hover:border-emerald-400 hover:shadow-lg transition-all cursor-pointer">
                <div className="w-14 h-14 bg-emerald-50 text-emerald-600 rounded-2xl flex items-center justify-center">
                  <FileText size={28} />
                </div>
                <div>
                  <p className="text-xs text-gray-500 font-medium uppercase tracking-wider">Total Recebido</p>
                  <h3 className="text-3xl font-bold text-slate-800">{stats.total_requerimentos}</h3>
                </div>
              </Link>

              <div className="bg-white p-6 rounded-3xl shadow-sm border border-gray-100 flex items-center gap-4">
                <div className="w-14 h-14 bg-emerald-50 text-emerald-600 rounded-2xl flex items-center justify-center">
                  <Clock size={28} />
                </div>
                <div>
                  <p className="text-xs text-gray-500 font-medium uppercase tracking-wider">Pendentes</p>
                  <h3 className="text-3xl font-bold text-slate-800">{stats.pendentes}</h3>
                </div>
              </div>

              <div 
                onClick={handleOpenModal}
                className="bg-white p-6 rounded-3xl shadow-sm border border-gray-100 flex flex-col gap-4 hover:border-emerald-400 hover:shadow-lg transition-all cursor-pointer group"
              >
                <div className="flex items-center gap-4">
                  <div className="w-14 h-14 bg-emerald-50 text-emerald-600 rounded-2xl flex items-center justify-center">
                    <Users size={28} />
                  </div>
                  <div>
                    <p className="text-xs text-gray-500 font-medium uppercase tracking-wider">Gestão de equipe</p>
                  </div>
                </div>
                <div className="pt-2">
                  <button className="w-full py-2.5 bg-emerald-600 text-white rounded-xl font-bold text-sm hover:bg-emerald-700 transition-all shadow-sm flex items-center justify-center gap-2">
                    Ver administradores
                  </button>
                </div>
              </div>
            </div>

            {isModalOpen && (
              <div className="fixed inset-0 z-50 flex items-center justify-center p-4">
                <div className="absolute inset-0 bg-black/40 backdrop-blur-sm" onClick={() => setIsModalOpen(false)} />
                <div className="relative bg-white w-full max-w-md rounded-3xl shadow-2xl animate-in fade-in zoom-in duration-200">
                  <div className="p-6 border-b flex justify-between items-center">
                    <h3 className="text-xl font-bold text-slate-800 flex items-center gap-2">
                      <Users className="text-emerald-600" size={24} /> Equipe CRADT
                    </h3>
                    <button onClick={() => setIsModalOpen(false)} className="p-2 hover:bg-gray-100 rounded-full transition-colors">
                      <X size={20} className="text-gray-400" />
                    </button>
                  </div>
                  <div className="p-6 max-h-[60vh] overflow-y-auto space-y-3">
                    {admins.map((adm: any) => (
                      <div key={adm.id} className="p-4 bg-gray-50 rounded-2xl border border-gray-100">
                        <p className="font-bold text-slate-800">{adm.name}</p>
                        <p className="text-xs text-gray-500">{adm.email}</p>
                      </div>
                    ))}
                  </div>
                  <div className="p-4">
                    <button onClick={() => setIsModalOpen(false)} className="w-full py-3 bg-slate-100 text-slate-600 rounded-xl font-bold hover:bg-slate-200 transition-all">
                      Fechar
                    </button>
                  </div>
                </div>
              </div>
            )}
          </div>
        )}
      </main>
    </div>
  );
}