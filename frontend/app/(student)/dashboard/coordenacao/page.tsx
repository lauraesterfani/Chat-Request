'use client';

import { useEffect, useState } from 'react';
import { useAuth } from '../../../context/AuthContext';
import { Search, Loader2, Eye, FileText, ArrowLeft } from 'lucide-react';
import Link from 'next/link';
import { useRouter } from 'next/navigation';

export default function CoordenacaoDashboard() {
  const { user, token } = useAuth();
  const router = useRouter();
  const [filters, setFilters] = useState({ name: '' });
  const [requests, setRequests] = useState([]);
  const [loading, setLoading] = useState(true);

  const fetchRequests = async () => {
    setLoading(true);
    try {
      const response = await fetch(`${process.env.NEXT_PUBLIC_API_URL}/requests?name=${filters.name}`, {
        headers: { 'Authorization': `Bearer ${token}` },
      });
      const data = await response.json();
      // Filtra apenas deferidos
      setRequests(data.filter((req: any) => req.status === 'completed'));
    } catch (err) {
      console.error('Erro ao carregar requerimentos', err);
    } finally {
      setLoading(false);
    }
  };

  useEffect(() => {
    if (user?.role === 'coordenacao') {
      fetchRequests();
    }
  }, [user]);

  const handleSearch = (e: React.FormEvent) => {
    e.preventDefault();
    fetchRequests();
  };

  return (
    <div className="min-h-screen bg-[#F4F6F8] p-8">
      <div className="max-w-6xl mx-auto">
        
        {/* Header com Botão de Voltar */}
        <div className="mb-8 flex items-center gap-4">
            
          
          <h1 className="text-3xl font-bold text-[#000000] flex items-center gap-2">
            <FileText className="text-emerald-600" /> Requerimentos Deferidos
          </h1>
        </div>

        {/* Filtro por Nome - Versão Compacta */}
        <div className="bg-white p-4 rounded-2xl shadow-sm border border-gray-100 mb-6">
          <form onSubmit={handleSearch} className="flex flex-col md:flex-row items-center gap-3">
            <div className="flex-1 w-full">
              <div className="relative">
                <Search className="absolute left-3 top-1/2 -translate-y-1/2 text-gray-400" size={18} />
                <input
                  type="text"
                  name="name"
                  value={filters.name}
                  onChange={(e) => setFilters({ ...filters, name: e.target.value })}
                  placeholder="Buscar solicitante por nome..."
                  className="w-full pl-10 pr-4 py-2 bg-gray-50 border border-gray-200 rounded-xl focus:ring-2 focus:ring-emerald-500 outline-none text-sm transition-all"
                />
              </div>
            </div>

            <button 
              type="submit" 
              className="w-full md:w-auto bg-[#004d40] text-white px-6 py-2 rounded-xl font-bold hover:opacity-90 transition-all text-sm flex items-center justify-center gap-2"
            >
              Pesquisar
            </button>
          </form>
        </div>

        {/* Tabela */}
        <div className="bg-white rounded-3xl shadow-sm border border-gray-100 overflow-hidden">
          <table className="w-full text-sm text-left">
            <thead className="bg-gray-50 text-gray-500 font-medium border-b border-gray-100">
              <tr>
                <th className="px-6 py-4">Protocolo</th>
                <th className="px-6 py-4">Assunto</th>
                <th className="px-6 py-4">Solicitante</th>
                <th className="px-6 py-4">Status</th>
                <th className="px-6 py-4 text-center">Ações</th>
              </tr>
            </thead>
            <tbody className="divide-y divide-gray-50">
              {loading ? (
                <tr>
                  <td colSpan={5} className="px-6 py-12 text-center">
                    <div className="flex justify-center items-center gap-2 text-emerald-600 font-bold">
                      <Loader2 className="animate-spin" /> Carregando...
                    </div>
                  </td>
                </tr>
              ) : requests.length === 0 ? (
                <tr>
                  <td colSpan={5} className="px-6 py-12 text-center text-gray-400">Nenhum pedido deferido encontrado.</td>
                </tr>
              ) : (
                requests.map((req: any) => (
                  <tr key={req.id} className="hover:bg-emerald-50/30 transition-colors">
                    <td className="px-6 py-4 font-mono text-xs text-gray-500">{req.protocol || `#${req.id}`}</td>
                    <td className="px-6 py-4 font-medium text-gray-900">{req.type?.name || req.subject}</td>
                    <td className="px-6 py-4">
                      <div className="flex flex-col">
                        <span className="text-gray-700 font-medium">{req.user?.name}</span>
                        <span className="text-[10px] text-gray-400">{req.user?.course?.name}</span>
                      </div>
                    </td>
                    <td className="px-6 py-4">
                      <span className="px-3 py-1 rounded-full text-[10px] font-bold uppercase tracking-wider bg-green-100 text-green-700 border border-green-200">
                        Deferido
                      </span>
                    </td>
                    <td className="px-6 py-4 text-center">
                      <Link 
                        href={`/requests/visualizar/${req.id}`} 
                        className="inline-flex items-center justify-center p-2 text-emerald-600 bg-emerald-50 rounded-full hover:bg-[#004d40] hover:text-white transition-all shadow-sm"
                      >
                        <Eye size={18} />
                      </Link>
                    </td>
                  </tr>
                ))
              )}
            </tbody>
          </table>
        </div>
      </div>
    </div>
  );
}