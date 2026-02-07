"use client";

import { useState, useEffect } from "react";
import axios from "axios";
import { Trash2, UserPlus, Users, X, Save, ShieldAlert } from "lucide-react";

const API_BASE = "http://127.0.0.1:8000/api";

export default function StaffPage() {
  const [staffs, setStaffs] = useState<any[]>([]);
  const [loading, setLoading] = useState(true);
  const [modalOpen, setModalOpen] = useState(false);
  
  // Dados do Formulário
  const [formData, setFormData] = useState({
    name: "",
    email: "",
    password: ""
  });

  const fetchStaffs = async () => {
    try {
      const token = localStorage.getItem("jwt_token");
      const res = await axios.get(`${API_BASE}/staffs`, {
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
      await axios.post(`${API_BASE}/staffs`, formData, {
        headers: { Authorization: `Bearer ${token}` },
      });
      
      setModalOpen(false);
      setFormData({ name: "", email: "", password: "" }); // Limpa form
      fetchStaffs(); // Recarrega lista
      alert("Funcionário cadastrado com sucesso!");
    } catch (error: any) {
      alert("Erro ao criar. Verifique se o email já existe.");
    }
  };

  const handleDelete = async (id: number) => {
    if (!confirm("Tem certeza que deseja remover este membro da equipe?")) return;
    try {
      const token = localStorage.getItem("jwt_token");
      await axios.delete(`${API_BASE}/staffs/${id}`, {
        headers: { Authorization: `Bearer ${token}` },
      });
      fetchStaffs();
    } catch (error: any) {
      alert(error.response?.data?.error || "Erro ao excluir.");
    }
  };

  return (
    <div className="min-h-screen bg-[#F4F6F8] p-8">
      <div className="max-w-5xl mx-auto">
        
        <div className="flex justify-between items-center mb-8">
          <div>
            <h1 className="text-3xl font-bold text-[#0B0D3A] flex items-center gap-2">
              <Users className="text-purple-600" /> Gestão de Equipe (CRADT)
            </h1>
            <p className="text-gray-500 text-sm">Gerencie os administradores do sistema</p>
          </div>
          <button 
            onClick={() => setModalOpen(true)}
            className="bg-purple-600 text-white px-4 py-2 rounded-xl flex items-center gap-2 font-bold hover:bg-purple-700 transition-all shadow-md"
          >
            <UserPlus size={20} /> Novo Admin
          </button>
        </div>

        <div className="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
            {loading ? (
                <div className="p-8 text-center text-gray-500">Carregando...</div>
            ) : (
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
                            <tr key={user.id} className="hover:bg-purple-50/30 transition-colors">
                                <td className="px-6 py-4 font-bold text-gray-800">{user.name}</td>
                                <td className="px-6 py-4 text-gray-500 text-sm">{user.email}</td>
                                <td className="px-6 py-4">
                                    <span className="px-2 py-1 bg-purple-100 text-purple-700 rounded-md text-xs font-bold uppercase">
                                        {user.role}
                                    </span>
                                </td>
                                <td className="px-6 py-4 text-right">
                                    <button 
                                        onClick={() => handleDelete(user.id)}
                                        className="p-2 text-red-400 hover:text-red-600 hover:bg-red-50 rounded-lg transition-colors"
                                        title="Remover Acesso"
                                    >
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
            <div className="bg-white rounded-2xl shadow-xl w-full max-w-md p-6 animate-in fade-in zoom-in duration-200">
                <div className="flex justify-between items-center mb-6 border-b pb-4">
                    <h2 className="text-xl font-bold text-gray-800 flex items-center gap-2">
                        <ShieldAlert className="text-purple-600" size={20}/> Novo Administrador
                    </h2>
                    <button onClick={() => setModalOpen(false)} className="text-gray-400 hover:text-red-500 transition-colors">
                        <X size={24} />
                    </button>
                </div>

                <form onSubmit={handleSave} className="space-y-4">
                    <div>
                        <label className="block text-sm font-bold text-gray-700 mb-1">Nome Completo</label>
                        <input 
                            type="text" 
                            value={formData.name}
                            onChange={(e) => setFormData({...formData, name: e.target.value})}
                            className="w-full px-4 py-3 bg-gray-50 border border-gray-200 rounded-xl focus:ring-2 focus:ring-purple-500 outline-none"
                            required
                        />
                    </div>

                    <div>
                        <label className="block text-sm font-bold text-gray-700 mb-1">Email de Acesso</label>
                        <input 
                            type="email" 
                            value={formData.email}
                            onChange={(e) => setFormData({...formData, email: e.target.value})}
                            className="w-full px-4 py-3 bg-gray-50 border border-gray-200 rounded-xl focus:ring-2 focus:ring-purple-500 outline-none"
                            required
                        />
                    </div>

                    <div>
                        <label className="block text-sm font-bold text-gray-700 mb-1">Senha Inicial</label>
                        <input 
                            type="password" 
                            value={formData.password}
                            onChange={(e) => setFormData({...formData, password: e.target.value})}
                            className="w-full px-4 py-3 bg-gray-50 border border-gray-200 rounded-xl focus:ring-2 focus:ring-purple-500 outline-none"
                            required
                            minLength={6}
                        />
                    </div>
                    
                    <div className="flex gap-3 pt-4">
                        <button 
                            type="button" 
                            onClick={() => setModalOpen(false)}
                            className="flex-1 px-4 py-3 bg-gray-100 text-gray-600 font-bold rounded-xl hover:bg-gray-200"
                        >
                            Cancelar
                        </button>
                        <button 
                            type="submit" 
                            className="flex-1 px-4 py-3 bg-purple-600 text-white font-bold rounded-xl hover:bg-purple-700 flex justify-center items-center gap-2 shadow-lg shadow-purple-200"
                        >
                            <UserPlus size={18} /> Criar Admin
                        </button>
                    </div>
                </form>
            </div>
        </div>
      )}
    </div>
  );
}