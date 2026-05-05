"use client";

import { useEffect, useState } from "react";
import axios from "axios";
import Link from "next/link";
import { useRouter } from "next/navigation"; // Importado para funcionalidade de voltar
import { Eye, Loader2, FileText, Search, FilterX, ArrowLeft } from "lucide-react"; // ArrowLeft adicionado

const API_BASE = "/api";

export default function RequestsPage() {
  const router = useRouter(); // Hook para navegação
  const [allRequests, setAllRequests] = useState<any[]>([]);
  const [filteredRequests, setFilteredRequests] = useState<any[]>([]);
  const [courses, setCourses] = useState<any[]>([]);
  const [loading, setLoading] = useState(true);

  const [filters, setFilters] = useState({
    name: "",
    matricula: "",
    course_id: "",
    status: ""
  });

  const fetchCourses = async () => {
    try {
      const res = await axios.get(`${API_BASE}/courses`);
      setCourses(res.data || []);
    } catch (error) {
      console.error("Erro ao carregar cursos:", error);
    }
  };

  const fetchRequests = async () => {
    setLoading(true);
    try {
      const token = localStorage.getItem("jwt_token");
      const res = await axios.get(`${API_BASE}/requests`, {
        headers: { Authorization: `Bearer ${token}` }
      });

      const dados = Array.isArray(res.data) ? res.data : (res.data.data || []);

      dados.sort((a: any, b: any) => {
        if (a.status === "pending" && b.status !== "pending") return -1;
        if (a.status !== "pending" && b.status === "pending") return 1;
        return 0;
      });

      setAllRequests(dados);
      const iniciais = dados.filter((req: any) => req.status !== "canceled");
      setFilteredRequests(iniciais);

    } catch (error) {
      console.error("Erro ao buscar requerimentos:", error);
    } finally {
      setLoading(false);
    }
  };

  useEffect(() => {
    fetchCourses();
    fetchRequests();
  }, []);

  const handleSearch = (e: React.FormEvent) => {
    e.preventDefault();
    let result = [...allRequests];

    if (filters.name) {
      result = result.filter(req =>
        req.user?.name?.toLowerCase().includes(filters.name.toLowerCase())
      );
    }

    if (filters.matricula) {
      result = result.filter(req =>
        (req.user?.enrollment_number || req.user?.matricula)?.includes(filters.matricula)
      );
    }

    if (filters.course_id) {
      result = result.filter(req => String(req.user?.course_id) === String(filters.course_id));
    }

    if (filters.status) {
      result = result.filter(req => req.status === filters.status);
    } else {
      result = result.filter(req => req.status !== "canceled");
    }

    setFilteredRequests(result);
  };

  const clearFilters = () => {
    setFilters({ name: "", matricula: "", course_id: "", status: "" });
    setFilteredRequests(allRequests.filter(req => req.status !== "canceled"));
  };

  const handleFilterChange = (e: React.ChangeEvent<HTMLInputElement | HTMLSelectElement>) => {
    const { name, value } = e.target;
    setFilters((prev) => ({ ...prev, [name]: value }));
  };

  return (
    <div className="min-h-screen bg-[#F4F6F8] p-8">
      <div className="max-w-6xl mx-auto">
        
        {/* Header com Botão de Voltar */}
        <div className="mb-8 flex items-center gap-4">
          <button 
            onClick={() => router.back()} 
            className="p-2 bg-white rounded-full shadow-sm border border-gray-100 text-gray-600 hover:text-emerald-600 hover:bg-emerald-50 transition-all"
            title="Voltar"
          >
            <ArrowLeft size={24} />
          </button>
          <h1 className="text-3xl font-bold text-[#000000] flex items-center gap-2">
            <FileText className="text-emerald-600" /> Todos os Requerimentos
          </h1>
        </div>

        {/* Filtros */}
        <div className="bg-white p-5 rounded-2xl shadow-sm border border-gray-100 mb-6">
          <form onSubmit={handleSearch} className="grid grid-cols-1 md:grid-cols-5 gap-4 items-end">
            <div>
              <label className="block text-xs font-bold text-gray-500 uppercase mb-1 ml-1">Nome</label>
              <input type="text" name="name" value={filters.name} onChange={handleFilterChange} placeholder="Ex: Dylan" className="w-full px-4 py-2.5 bg-gray-50 border border-gray-200 rounded-xl focus:ring-2 focus:ring-emerald-500 outline-none text-sm" />
            </div>

            <div>
              <label className="block text-xs font-bold text-gray-500 uppercase mb-1 ml-1">Matrícula</label>
              <input type="text" name="matricula" value={filters.matricula} onChange={handleFilterChange} placeholder="Ex: 2024..." className="w-full px-4 py-2.5 bg-gray-50 border border-gray-200 rounded-xl focus:ring-2 focus:ring-emerald-500 outline-none text-sm" />
            </div>

            <div>
              <label className="block text-xs font-bold text-gray-500 uppercase mb-1 ml-1">Curso</label>
              <select name="course_id" value={filters.course_id} onChange={handleFilterChange} className="w-full px-4 py-2.5 bg-gray-50 border border-gray-200 rounded-xl focus:ring-2 focus:ring-emerald-500 outline-none text-sm cursor-pointer">
                <option value="">Todos os Cursos</option>
                {courses.map((course) => (
                  <option key={course.id} value={course.id}>{course.name}</option>
                ))}
              </select>
            </div>

            <div>
              <label className="block text-xs font-bold text-gray-500 uppercase mb-1 ml-1">Status</label>
              <select name="status" value={filters.status} onChange={handleFilterChange} className="w-full px-4 py-2.5 bg-gray-50 border border-gray-200 rounded-xl focus:ring-2 focus:ring-emerald-500 outline-none text-sm cursor-pointer">
                <option value="">Todos </option>
                <option value="pending">Pendentes</option>
                <option value="analyzing">Em Análise</option>
                <option value="completed">Deferidos</option>
                <option value="canceled">Indeferidos</option>
              </select>
            </div>

            <div className="flex gap-2">
              <button type="submit" className="flex-1 bg-[#004d40] text-white px-4 py-2.5 rounded-xl font-bold hover:opacity-90 transition-all flex justify-center items-center gap-2 text-sm">
                <Search size={16} /> Filtrar
              </button>
              {(filters.name || filters.matricula || filters.course_id || filters.status) && (
                <button type="button" onClick={clearFilters} className="px-3 py-2.5 bg-red-50 text-red-600 border border-red-100 rounded-xl hover:bg-red-100 transition-all">
                  <FilterX size={18} />
                </button>
              )}
            </div>
          </form>
        </div>

        <div className="bg-white rounded-3xl shadow-sm border border-gray-100 overflow-hidden">
          <table className="w-full text-sm text-left">
            <thead className="bg-gray-50 text-gray-500 font-medium border-b border-gray-100">
              <tr>
                <th className="px-6 py-4">Protocolo</th>
                <th className="px-6 py-4">Assunto</th>
                <th className="px-6 py-4">Solicitante</th>
                <th className="px-6 py-4">Curso</th>
                <th className="px-6 py-4">Status</th>
                <th className="px-6 py-4 text-center">Ações</th>
              </tr>
            </thead>
            <tbody className="divide-y divide-gray-50">
              {loading ? (
                <tr>
                  <td colSpan={6} className="px-6 py-12 text-center">
                    <div className="flex justify-center items-center gap-2 text-emerald-600 font-bold">
                      <Loader2 className="animate-spin" /> Carregando...
                    </div>
                  </td>
                </tr>
              ) : filteredRequests.length === 0 ? (
                <tr>
                  <td colSpan={6} className="px-6 py-12 text-center text-gray-400">Nenhum pedido encontrado.</td>
                </tr>
              ) : (
                filteredRequests.map((req) => (
                  <tr key={req.id} className="hover:bg-blue-50/30 transition-colors">
                    <td className="px-6 py-4 font-mono text-xs text-gray-500">{req.protocol || `#${req.id}`}</td>
                    <td className="px-6 py-4 font-medium text-gray-900">{req.type?.name || req.subject}</td>
                    <td className="px-6 py-4 text-gray-600">
                      <div className="flex flex-col">
                        <span className="font-bold text-xs text-slate-700">{req.user?.name || "---"}</span>
                        <span className="text-[10px] text-slate-400">{req.user?.enrollment_number || req.user?.matricula}</span>
                      </div>
                    </td>
                    <td className="px-6 py-4 text-gray-500 text-xs">{req.user?.course?.name || "---"}</td>
                  <td className="px-6 py-4">
  <span className={`px-3 py-1 rounded-full text-[10px] font-bold uppercase tracking-wider ${
    req.status === 'completed' ? 'bg-green-100 text-green-700 border border-green-200' : 
    req.status === 'analyzing' ? 'bg-amber-100 text-amber-700 border border-amber-200' : 
    req.status === 'pending'   ? 'bg-red-100 text-red-700 border border-red-200' :
    req.status === 'canceled'  ? 'bg-red-50 text-red-600 border border-red-100' :
    'bg-slate-100 text-slate-500'
  }`}>
    {req.status === 'pending' ? 'Pendente' : 
     req.status === 'analyzing' ? 'Em Análise' : 
     req.status === 'completed' ? 'Deferido' : 
     'Indeferido'}
  </span>
</td>
                    <td className="px-6 py-4 text-center">
                      <Link href={`/requests/acesso/${req.id}`} className="inline-flex items-center justify-center p-2 text-emerald-600 bg-emerald-50 rounded-full hover:bg-blue-600 hover:text-white transition-all">
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