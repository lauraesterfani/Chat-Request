"use client";

import { useEffect, useState } from "react";
import axios from "axios";
import Link from "next/link";
import { Eye, Loader2, Plus, FileText, Search, FilterX } from "lucide-react";

const API_BASE = "http://127.0.0.1:8000/api";

export default function RequestsPage() {
  const [requests, setRequests] = useState<any[]>([]);
  const [courses, setCourses] = useState<any[]>([]);
  const [loading, setLoading] = useState(true);

  // Estados dos Filtros
  const [filters, setFilters] = useState({
    name: "",
    matricula: "",
    course_id: ""
  });

  // Busca Cursos para o Select
  const fetchCourses = async () => {
    try {
      const res = await axios.get(`${API_BASE}/courses`);
      setCourses(res.data || []);
    } catch (error) {
      console.error("Erro ao carregar cursos:", error);
    }
  };

  // Busca Requerimentos (com filtros opcionais)
  const fetchRequests = async (appliedFilters = {}) => {
    setLoading(true);
    try {
      const token = localStorage.getItem("jwt_token");
      const res = await axios.get(`${API_BASE}/requests`, {
        headers: { Authorization: `Bearer ${token}` },
        params: appliedFilters, // Envia filtros na URL
      });
      
      const dados = Array.isArray(res.data) ? res.data : (res.data.data || []);
      setRequests(dados);
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

  // Atualiza inputs
  const handleFilterChange = (e: React.ChangeEvent<HTMLInputElement | HTMLSelectElement>) => {
    const { name, value } = e.target;
    setFilters((prev) => ({ ...prev, [name]: value }));
  };

  // Botão Pesquisar
  const handleSearch = (e: React.FormEvent) => {
    e.preventDefault();
    const cleanFilters: any = {};
    if (filters.name) cleanFilters.name = filters.name;
    if (filters.matricula) cleanFilters.matricula = filters.matricula;
    if (filters.course_id) cleanFilters.course_id = filters.course_id;
    
    fetchRequests(cleanFilters);
  };

  // Botão Limpar
  const clearFilters = () => {
    setFilters({ name: "", matricula: "", course_id: "" });
    fetchRequests({});
  };

  return (
    <div className="min-h-screen bg-[#F4F6F8] p-8">
      <div className="max-w-6xl mx-auto">
        
        {/* Cabeçalho */}
        <div className="flex flex-col md:flex-row justify-between items-start md:items-center mb-8 gap-4">
          <div>
            <h1 className="text-3xl font-bold text-[#0B0D3A] flex items-center gap-2">
              <FileText className="text-blue-600" /> Todos os Pedidos
            </h1>
            <p className="text-gray-500 text-sm mt-1">
              Gerencie e filtre as solicitações recebidas
            </p>
          </div>

          <Link href="/requests/new" className="bg-blue-600 hover:bg-blue-700 text-white px-5 py-3 rounded-xl font-bold flex items-center gap-2 transition-all shadow-md hover:shadow-lg">
            <Plus size={20} /> Novo Pedido
          </Link>
        </div>

        {/* --- ÁREA DE FILTROS --- */}
        <div className="bg-white p-5 rounded-2xl shadow-sm border border-gray-100 mb-6">
          <form onSubmit={handleSearch} className="grid grid-cols-1 md:grid-cols-4 gap-4 items-end">
            
            {/* Input Nome */}
            <div>
              <label className="block text-xs font-bold text-gray-500 uppercase mb-1 ml-1">Nome do Aluno</label>
              <input
                type="text"
                name="name"
                value={filters.name}
                onChange={handleFilterChange}
                placeholder="Ex: Dylan"
                className="w-full px-4 py-2.5 bg-gray-50 border border-gray-200 rounded-xl focus:ring-2 focus:ring-blue-500 outline-none text-sm transition-all"
              />
            </div>

            {/* Input Matrícula */}
            <div>
              <label className="block text-xs font-bold text-gray-500 uppercase mb-1 ml-1">Matrícula</label>
              <input
                type="text"
                name="matricula"
                value={filters.matricula}
                onChange={handleFilterChange}
                placeholder="Ex: 2024..."
                className="w-full px-4 py-2.5 bg-gray-50 border border-gray-200 rounded-xl focus:ring-2 focus:ring-blue-500 outline-none text-sm transition-all"
              />
            </div>

            {/* Select Curso */}
            <div>
              <label className="block text-xs font-bold text-gray-500 uppercase mb-1 ml-1">Curso</label>
              <select
                name="course_id"
                value={filters.course_id}
                onChange={handleFilterChange}
                className="w-full px-4 py-2.5 bg-gray-50 border border-gray-200 rounded-xl focus:ring-2 focus:ring-blue-500 outline-none text-sm transition-all cursor-pointer"
              >
                <option value="">Todos os Cursos</option>
                {courses.map((course) => (
                  <option key={course.id} value={course.id}>
                    {course.name}
                  </option>
                ))}
              </select>
            </div>

            {/* Botões */}
            <div className="flex gap-2">
              <button
                type="submit"
                className="flex-1 bg-[#0B0D3A] text-white px-4 py-2.5 rounded-xl font-bold hover:opacity-90 transition-all flex justify-center items-center gap-2 text-sm"
              >
                <Search size={16} /> Filtrar
              </button>
              
              {(filters.name || filters.matricula || filters.course_id) && (
                <button
                  type="button"
                  onClick={clearFilters}
                  className="px-3 py-2.5 bg-red-50 text-red-600 border border-red-100 rounded-xl hover:bg-red-100 transition-all"
                  title="Limpar Filtros"
                >
                  <FilterX size={18} />
                </button>
              )}
            </div>

          </form>
        </div>

        {/* Tabela de Resultados */}
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
                    <div className="flex justify-center items-center gap-2 text-gray-400">
                      <Loader2 className="animate-spin" /> Carregando...
                    </div>
                  </td>
                </tr>
              ) : requests.length === 0 ? (
                <tr>
                  <td colSpan={6} className="px-6 py-12 text-center text-gray-400">
                    Nenhum pedido encontrado.
                  </td>
                </tr>
              ) : (
                requests.map((req) => (
                  <tr key={req.id} className="hover:bg-blue-50/30 transition-colors">
                    <td className="px-6 py-4 font-mono text-xs text-gray-500">
                      {req.protocol || `#${req.id}`}
                    </td>
                    <td className="px-6 py-4 font-medium text-gray-900">
                      {req.type?.name || req.subject}
                    </td>
                    <td className="px-6 py-4 text-gray-600">
                      <div className="flex flex-col">
                        <span className="font-bold text-xs text-slate-700">{req.user?.name || "---"}</span>
                        <span className="text-[10px] text-slate-400">{req.user?.enrollment_number || req.user?.matricula}</span>
                      </div>
                    </td>
                    <td className="px-6 py-4 text-gray-500 text-xs">
                      {req.user?.course?.name || "---"}
                    </td>
                    <td className="px-6 py-4">
                      <span className={`px-3 py-1 rounded-full text-[10px] font-bold uppercase tracking-wider ${
                        req.status === 'completed' ? 'bg-green-100 text-green-700 border border-green-200' : 
                        req.status === 'analyzing' ? 'bg-amber-100 text-amber-700 border border-amber-200' : 
                        req.status === 'canceled' ? 'bg-gray-100 text-gray-600 border border-gray-200' :
                        'bg-red-50 text-red-600 border border-red-100'
                      }`}>
                        {req.status === 'pending' ? 'Pendente' : 
                         req.status === 'analyzing' ? 'Em Análise' :
                         req.status === 'completed' ? 'Deferido' : 'Indeferido'}
                      </span>
                    </td>
                    <td className="px-6 py-4 text-center">
                      <Link href={`/requests/acesso/${req.id}`} className="inline-flex items-center justify-center p-2 text-blue-600 bg-blue-50 rounded-full hover:bg-blue-600 hover:text-white transition-all">
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