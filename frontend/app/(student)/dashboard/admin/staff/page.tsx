"use client";

import { useState, useEffect } from "react";
import axios from "axios";
import { Trash2, UserPlus, Users, X, ShieldAlert, ArrowLeft, CheckCircle, AlertTriangle } from "lucide-react";
import Link from "next/link";

const API_BASE = "/api";

export default function StaffPage() {
  const [staffs, setStaffs] = useState<any[]>([]);
  const [loading, setLoading] = useState(true);
  const [modalOpen, setModalOpen] = useState(false);
  const [successData, setSuccessData] = useState<any>(null);
  const [deleteId, setDeleteId] = useState<number | null>(null);
  const [errorMessage, setErrorMessage] = useState<string | null>(null);

  const [formData, setFormData] = useState({
    name: "",
    email: "",
    phone: "",
    cpf: "",
    role: "admin",
    course_id: ""
  });

  const [courses, setCourses] = useState<any[]>([]);

  useEffect(() => {
    const fetchCourses = async () => {
      try {
        const token = localStorage.getItem("jwt_token");
        const res = await axios.get(`${API_BASE}/courses`, {
          headers: { Authorization: `Bearer ${token}` },
        });
        setCourses(res.data);
      } catch (error) { console.error(error); }
    };
    fetchCourses();
  }, []);

  const fetchStaffs = async () => {
    setLoading(true);
    try {
      const token = localStorage.getItem("jwt_token");
      const res = await axios.get(`${API_BASE}/staff-admins`, {
        headers: { Authorization: `Bearer ${token}` },
      });
      setStaffs(res.data);
    } catch (error) {
      console.error("Erro ao buscar equipe", error);
    } finally {
      setLoading(false);
    }
  };

  useEffect(() => {
    fetchStaffs();
  }, []);

  const handleSave = async (e: React.FormEvent) => {
    e.preventDefault();
    try {
      const token = localStorage.getItem("jwt_token");
      const response = await axios.post(`${API_BASE}/staff-admins`, formData, {
        headers: { Authorization: `Bearer ${token}` },
      });

      setModalOpen(false);
      setFormData({ name: "", email: "", phone: "", cpf: "", role: "admin", course_id: "" });
      fetchStaffs();
      setSuccessData(response.data);
    } catch (error: any) {
      const msg = error.response?.data?.errors 
        ? JSON.stringify(error.response.data.errors) 
        : (error.response?.data?.error_tecnico || "Erro inesperado ao salvar.");
      setErrorMessage(msg);
    }
  };

  const confirmDelete = async () => {
    if (!deleteId) return;
    try {
      const token = localStorage.getItem("jwt_token");
      await axios.delete(`${API_BASE}/staff-admins/${deleteId}`, {
        headers: { Authorization: `Bearer ${token}` },
      });
      setDeleteId(null);
      fetchStaffs();
    } catch (error: any) {
      setErrorMessage(error.response?.data?.error || "Erro ao excluir.");
    }
  };

  return (
    <div className="min-h-screen bg-[#F4F6F8] p-8">
      <div className="max-w-5xl mx-auto">
        <Link href="/dashboard/admin" className="flex items-center gap-2 text-gray-500 hover:text-emerald-600 mb-4 transition-colors">
          <ArrowLeft size={20} /> Voltar
        </Link>

        <div className="flex justify-between items-center mb-8">
          <div>
            <h1 className="text-3xl font-bold text-[#0B0D3A] flex items-center gap-2">
              <Users className="text-emerald-600" /> Gestão de Equipe
            </h1>
            <p className="text-gray-500 text-sm">Gerencie os administradores e staff do sistema</p>
          </div>
          <button onClick={() => setModalOpen(true)} className="bg-emerald-600 text-white px-4 py-2 rounded-xl flex items-center gap-2 font-bold hover:bg-emerald-700 transition-all shadow-md">
            <UserPlus size={20} /> Novo Usuário
          </button>
        </div>

        <div className="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
          {loading ? <div className="p-8 text-center text-gray-500">Carregando...</div> : (
            <table className="w-full text-left">
              <thead className="bg-gray-50 text-gray-500 font-bold uppercase text-xs border-b border-gray-100">
                <tr>
                  <th className="px-6 py-4">Nome</th>
                  <th className="px-6 py-4">Email</th>
                  <th className="px-6 py-4">Função</th>
                  <th className="px-6 py-4 text-right">Ações</th>
                </tr>
              </thead>
              <tbody className="divide-y divide-gray-50">
                {staffs.map((user) => (
                  <tr key={user.id} className="hover:bg-emerald-50/30 transition-colors">
                    <td className="px-6 py-4 font-bold text-gray-800">{user.name}</td>
                    <td className="px-6 py-4 text-gray-500 text-sm">{user.email}</td>
                    <td className="px-6 py-4">
                      <span className="px-2 py-1 bg-emerald-100 text-emerald-700 rounded-md text-xs font-bold uppercase">{user.role}</span>
                    </td>
                    <td className="px-6 py-4 text-right">
                      <button onClick={() => setDeleteId(user.id)} className="p-2 text-red-400 hover:text-red-600 hover:bg-red-50 rounded-lg transition-colors">
                        <Trash2 size={18} />
                      </button>
                    </td>
                  </tr>
                ))}
              </tbody>
            </table>
          )}
        </div>
      </div>

      {/* MODAL DE CADASTRO */}
      {modalOpen && (
        <div className="fixed inset-0 bg-black/50 flex items-center justify-center z-50 p-4 backdrop-blur-sm">
          <div className="bg-white rounded-2xl shadow-xl w-full max-w-md flex flex-col max-h-[90vh] animate-in fade-in zoom-in duration-200">
            <div className="flex justify-between items-center p-6 border-b">
              <h2 className="text-xl font-bold text-gray-800 flex items-center gap-2">
                <ShieldAlert className="text-emerald-600" size={20} /> Novo Usuário
              </h2>
              <button onClick={() => setModalOpen(false)} className="text-gray-400 hover:text-red-500 transition-colors"><X size={24} /></button>
            </div>
            <form onSubmit={handleSave} className="p-6 space-y-4 overflow-y-auto">
              <div>
                <label className="block text-sm font-bold text-gray-700 mb-1">Nome Completo</label>
                <input type="text" value={formData.name} onChange={(e) => setFormData({ ...formData, name: e.target.value })} className="w-full px-4 py-3 bg-gray-50 border border-gray-200 rounded-xl focus:ring-2 focus:ring-emerald-500 outline-none" required />
              </div>
              <div>
                <label className="block text-sm font-bold text-gray-700 mb-1">Email de Acesso</label>
                <input type="email" value={formData.email} onChange={(e) => setFormData({ ...formData, email: e.target.value })} className="w-full px-4 py-3 bg-gray-50 border border-gray-200 rounded-xl focus:ring-2 focus:ring-emerald-500 outline-none" required />
              </div>
              <div className="grid grid-cols-2 gap-4">
                <div>
                  <label className="block text-sm font-bold text-gray-700 mb-1">Telefone</label>
                  <input type="text" value={formData.phone} onChange={(e) => setFormData({ ...formData, phone: e.target.value })} className="w-full px-4 py-3 bg-gray-50 border border-gray-200 rounded-xl focus:ring-2 focus:ring-emerald-500 outline-none" required />
                </div>
                <div>
                  <label className="block text-sm font-bold text-gray-700 mb-1">CPF</label>
                  <input type="text" value={formData.cpf} onChange={(e) => setFormData({ ...formData, cpf: e.target.value })} className="w-full px-4 py-3 bg-gray-50 border border-gray-200 rounded-xl focus:ring-2 focus:ring-emerald-500 outline-none" required />
                </div>
              </div>
              <div>
                <label className="block text-sm font-bold text-gray-700 mb-1">Função</label>
                <select value={formData.role} onChange={(e) => setFormData({ ...formData, role: e.target.value })} className="w-full px-4 py-3 bg-gray-50 border border-gray-200 rounded-xl focus:ring-2 focus:ring-emerald-500 outline-none" required>
                  <option value="admin">Admin</option>
                  <option value="staff">Staff</option>
                  <option value="coordenacao">Coordenação</option>
                </select>
              </div>
              {formData.role === "coordenacao" && (
                <div>
                  <label className="block text-sm font-bold text-gray-700 mb-1">Curso Vinculado</label>
                  <select value={formData.course_id || ""} onChange={(e) => setFormData({ ...formData, course_id: e.target.value })} className="w-full px-4 py-3 bg-gray-50 border border-gray-200 rounded-xl focus:ring-2 focus:ring-emerald-500 outline-none" required>
                    <option value="">Selecione um curso</option>
                    {courses.map((course) => <option key={course.id} value={course.id}>{course.name}</option>)}
                  </select>
                </div>
              )}
              <div className="flex gap-3 pt-4 sticky bottom-0 bg-white">
                <button type="button" onClick={() => setModalOpen(false)} className="flex-1 px-4 py-3 bg-gray-100 text-gray-600 font-bold rounded-xl hover:bg-gray-200">Cancelar</button>
                <button type="submit" className="flex-1 px-4 py-3 bg-emerald-600 text-white font-bold rounded-xl hover:bg-emerald-700 flex justify-center items-center gap-2 shadow-lg">Criar Usuário</button>
              </div>
            </form>
          </div>
        </div>
      )}

      {/* MODAL DE SUCESSO (CRIADO) */}
      {successData && (
        <div className="fixed inset-0 bg-black/50 flex items-center justify-center z-[60] p-4 backdrop-blur-sm">
          <div className="bg-white rounded-3xl shadow-2xl w-full max-w-sm p-8 text-center animate-in zoom-in duration-200">
            <div className="w-16 h-16 bg-emerald-100 text-emerald-600 rounded-full flex items-center justify-center mx-auto mb-4">
              <CheckCircle size={40} />
            </div>
            <h2 className="text-2xl font-bold text-gray-800 mb-2">Usuário Criado!</h2>
            <p className="text-gray-500 mb-6">Senha temporária gerada:</p>
            <div className="bg-emerald-50 border-2 border-dashed border-emerald-200 p-4 rounded-2xl mb-6 font-mono text-2xl font-bold text-emerald-700 tracking-wider">
              {successData.temp_password}
            </div>
            <button onClick={() => setSuccessData(null)} className="w-full py-4 bg-emerald-600 text-white font-bold rounded-2xl hover:bg-emerald-700 transition-all">Concluído</button>
          </div>
        </div>
      )}

      {/* MODAL DE CONFIRMAÇÃO DE EXCLUSÃO */}
      {deleteId && (
        <div className="fixed inset-0 bg-black/50 flex items-center justify-center z-[60] p-4 backdrop-blur-sm">
          <div className="bg-white rounded-3xl shadow-2xl w-full max-w-sm p-8 text-center animate-in zoom-in duration-200">
            <div className="w-16 h-16 bg-red-100 text-red-600 rounded-full flex items-center justify-center mx-auto mb-4">
              <AlertTriangle size={32} />
            </div>
            <h2 className="text-xl font-bold text-gray-800 mb-2">Remover Acesso?</h2>
            <p className="text-gray-500 mb-8 text-sm">Esta ação é permanente e o membro não poderá mais acessar o painel administrativo.</p>
            <div className="flex gap-3">
              <button onClick={() => setDeleteId(null)} className="flex-1 py-3 bg-gray-100 text-gray-600 font-bold rounded-xl hover:bg-gray-200 transition-all">Cancelar</button>
              <button onClick={confirmDelete} className="flex-1 py-3 bg-red-500 text-white font-bold rounded-xl hover:bg-red-600 transition-all shadow-lg shadow-red-100">Sim, Remover</button>
            </div>
          </div>
        </div>
      )}

      {/* MODAL DE ERRO */}
      {errorMessage && (
        <div className="fixed inset-0 bg-black/50 flex items-center justify-center z-[70] p-4 backdrop-blur-sm">
          <div className="bg-white rounded-3xl shadow-2xl w-full max-w-sm p-8 text-center">
            <div className="w-16 h-16 bg-amber-100 text-amber-600 rounded-full flex items-center justify-center mx-auto mb-4">
              <ShieldAlert size={32} />
            </div>
            <h2 className="text-xl font-bold text-gray-800 mb-2">Ops! Algo deu errado</h2>
            <p className="text-gray-500 mb-8 text-sm">{errorMessage}</p>
            <button onClick={() => setErrorMessage(null)} className="w-full py-3 bg-gray-800 text-white font-bold rounded-xl hover:bg-black transition-all">Tentar novamente</button>
          </div>
        </div>
      )}
    </div>
  );
}