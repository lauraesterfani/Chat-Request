"use client";

import { useState, useEffect } from "react";
import axios from "axios";
import { Trash2, Edit, Plus, X, Save, FileType, AlertCircle } from "lucide-react";

const API_BASE = "http://127.0.0.1:8000/api";

export default function TypeRequestsPage() {
  const [types, setTypes] = useState<any[]>([]);
  const [loading, setLoading] = useState(true);
  const [modalOpen, setModalOpen] = useState(false);
  const [editingType, setEditingType] = useState<any>(null);
  
  // Campos do Formulário
  const [formData, setFormData] = useState({
    name: "",
    description: "",
    icon: "" // Se quiser usar no futuro
  });

  const fetchTypes = async () => {
    try {
      const token = localStorage.getItem("jwt_token");
      const res = await axios.get(`${API_BASE}/type-requests`, {
        headers: { Authorization: `Bearer ${token}` },
      });
      setTypes(res.data);
    } catch (error) {
      console.error("Erro ao buscar tipos", error);
    } finally {
      setLoading(false);
    }
  };

  useEffect(() => {
    fetchTypes();
  }, []);

  const openModal = (type: any = null) => {
    setEditingType(type);
    if (type) {
      setFormData({ name: type.name, description: type.description || "", icon: type.icon || "" });
    } else {
      setFormData({ name: "", description: "", icon: "" });
    }
    setModalOpen(true);
  };

  const handleSave = async (e: React.FormEvent) => {
    e.preventDefault();
    try {
      const token = localStorage.getItem("jwt_token");
      const headers = { Authorization: `Bearer ${token}` };

      if (editingType) {
        await axios.put(`${API_BASE}/type-requests/${editingType.id}`, formData, { headers });
      } else {
        await axios.post(`${API_BASE}/type-requests`, formData, { headers });
      }

      setModalOpen(false);
      fetchTypes();
    } catch (error: any) {
      console.error(error);
      alert(error.response?.data?.message || "Erro ao salvar. Verifique os dados.");
    }
  };

  const handleDelete = async (id: string) => {
    if (!confirm("Tem certeza que deseja excluir este tipo?")) return;
    try {
      const token = localStorage.getItem("jwt_token");
      await axios.delete(`${API_BASE}/type-requests/${id}`, {
        headers: { Authorization: `Bearer ${token}` },
      });
      fetchTypes();
    } catch (error) {
      alert("Erro ao excluir.");
    }
  };

  return (
    <div className="min-h-screen bg-[#F4F6F8] p-8">
      <div className="max-w-5xl mx-auto">
        
        <div className="flex justify-between items-center mb-8">
          <div>
            <h1 className="text-3xl font-bold text-[#0B0D3A] flex items-center gap-2">
              <FileType className="text-blue-600" /> Tipos de Requerimento
            </h1>
            <p className="text-gray-500 text-sm">Gerencie as opções disponíveis para os alunos</p>
          </div>
          <button 
            onClick={() => openModal()}
            className="bg-blue-600 text-white px-4 py-2 rounded-xl flex items-center gap-2 font-bold hover:bg-blue-700 transition-all shadow-md"
          >
            <Plus size={20} /> Novo Tipo
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
                            <th className="px-6 py-4">Descrição</th>
                            <th className="px-6 py-4 text-right">Ações</th>
                        </tr>
                    </thead>
                    <tbody className="divide-y divide-gray-50">
                        {types.map((type) => (
                            <tr key={type.id} className="hover:bg-blue-50/30 transition-colors">
                                <td className="px-6 py-4 font-bold text-gray-800">{type.name}</td>
                                <td className="px-6 py-4 text-gray-500 text-sm">{type.description || "---"}</td>
                                <td className="px-6 py-4 text-right flex justify-end gap-2">
                                    <button 
                                        onClick={() => openModal(type)}
                                        className="p-2 text-blue-600 hover:bg-blue-100 rounded-lg transition-colors"
                                    >
                                        <Edit size={18} />
                                    </button>
                                    <button 
                                        onClick={() => handleDelete(type.id)}
                                        className="p-2 text-red-600 hover:bg-red-100 rounded-lg transition-colors"
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

      {/* MODAL */}
      {modalOpen && (
        <div className="fixed inset-0 bg-black/50 flex items-center justify-center z-50 p-4 backdrop-blur-sm">
            <div className="bg-white rounded-2xl shadow-xl w-full max-w-md p-6 animate-in fade-in zoom-in duration-200">
                <div className="flex justify-between items-center mb-6 border-b pb-4">
                    <h2 className="text-xl font-bold text-gray-800">
                        {editingType ? "Editar Tipo" : "Novo Tipo"}
                    </h2>
                    <button onClick={() => setModalOpen(false)} className="text-gray-400 hover:text-red-500 transition-colors">
                        <X size={24} />
                    </button>
                </div>

                <form onSubmit={handleSave} className="space-y-4">
                    <div>
                        <label className="block text-sm font-bold text-gray-700 mb-1">Nome</label>
                        <input 
                            type="text" 
                            value={formData.name}
                            onChange={(e) => setFormData({...formData, name: e.target.value})}
                            placeholder="Ex: Abono de Faltas"
                            className="w-full px-4 py-3 bg-gray-50 border border-gray-200 rounded-xl focus:ring-2 focus:ring-blue-500 outline-none transition-all"
                            required
                        />
                    </div>

                    <div>
                        <label className="block text-sm font-bold text-gray-700 mb-1">Descrição (Opcional)</label>
                        <textarea 
                            value={formData.description}
                            onChange={(e) => setFormData({...formData, description: e.target.value})}
                            placeholder="Ex: Solicitação para justificar ausências..."
                            className="w-full px-4 py-3 bg-gray-50 border border-gray-200 rounded-xl focus:ring-2 focus:ring-blue-500 outline-none transition-all h-24 resize-none"
                        />
                    </div>
                    
                    <div className="flex gap-3 pt-4">
                        <button 
                            type="button" 
                            onClick={() => setModalOpen(false)}
                            className="flex-1 px-4 py-3 bg-gray-100 text-gray-600 font-bold rounded-xl hover:bg-gray-200 transition-colors"
                        >
                            Cancelar
                        </button>
                        <button 
                            type="submit" 
                            className="flex-1 px-4 py-3 bg-blue-600 text-white font-bold rounded-xl hover:bg-blue-700 flex justify-center items-center gap-2 transition-colors shadow-lg shadow-blue-200"
                        >
                            <Save size={18} /> Salvar
                        </button>
                    </div>
                </form>
            </div>
        </div>
      )}
    </div>
  );
}