"use client";

import { useState, useEffect } from "react";
import axios from "axios";
import { Trash2, Edit, Plus, X, Save, FileType, FileCheck2 } from "lucide-react";

const API_BASE = "/api";

export default function TypeRequestsPage() {
  const [types, setTypes] = useState<any[]>([]);
  const [loading, setLoading] = useState(true);
  const [modalOpen, setModalOpen] = useState(false);
  const [editingType, setEditingType] = useState<any>(null);
  
  // Estado para guardar o papel do usuário
  const [userRole, setUserRole] = useState<string>("");

  // Campos do Formulário (🔮 Atualizado com os novos campos)
  const [formData, setFormData] = useState({
    name: "",
    description: "",
    icon: "",
    requires_document: false,
    document_instructions: ""
  });

  const fetchTypes = async () => {
    try {
      const token = localStorage.getItem("jwt_token");
      const res = await axios.get(`${API_BASE}/type-requests`, {
        headers: { Authorization: `Bearer ${token}` },
      });
      // Tratamento caso a resposta venha envelopada em .data
      const dados = Array.isArray(res.data) ? res.data : (res.data.data || []);
      setTypes(dados);
    } catch (error) {
      console.error("Erro ao buscar tipos", error);
    } finally {
      setLoading(false);
    }
  };

  useEffect(() => {
    fetchTypes();

    // Busca os dados do usuário atual para saber o Role
    const fetchMe = async () => {
        try {
            const token = localStorage.getItem("jwt_token");
            const res = await axios.get(`${API_BASE}/me`, {
                headers: { Authorization: `Bearer ${token}` },
            });
            setUserRole(res.data.role);
        } catch (error) {
            console.error("Erro ao buscar permissões do usuário", error);
        }
    };
    fetchMe();
  }, []);

  const openModal = (type: any = null) => {
    setEditingType(type);
    if (type) {
      setFormData({ 
        name: type.name, 
        description: type.description || "", 
        icon: type.icon || "",
        requires_document: Boolean(type.requires_document),
        document_instructions: type.document_instructions || ""
      });
    } else {
      setFormData({ 
        name: "", 
        description: "", 
        icon: "",
        requires_document: false,
        document_instructions: ""
      });
    }
    setModalOpen(true);
  };

  const handleSave = async (e: React.FormEvent) => {
    e.preventDefault();
    try {
      const token = localStorage.getItem("jwt_token");
      const headers = { Authorization: `Bearer ${token}` };

      // Limpa as instruções caso o admin tenha mudado para "Não precisa" antes de salvar
      const dataToSave = {
        ...formData,
        document_instructions: formData.requires_document ? formData.document_instructions : ""
      };

      if (editingType) {
        await axios.put(`${API_BASE}/type-requests/${editingType.id}`, dataToSave, { headers });
      } else {
        await axios.post(`${API_BASE}/type-requests`, dataToSave, { headers });
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
    } catch (error: any) {
      const mensagemErro = error.response?.data?.error || "Erro ao excluir.";
      alert(mensagemErro);
    }
  };

  return (
    <div className="min-h-screen bg-[#fcfcfc] p-4 sm:p-6">
      <div className="max-w-5xl mx-auto">
        
        <div className="flex justify-between items-center mb-8">
          <div>
            <h1 className="text-3xl font-bold text-slate-800 tracking-tighter flex items-center gap-2">
              <FileType className="text-[#108542]" /> Tipos de Requerimento
            </h1>
            <p className="text-slate-500 text-sm">Gerencie as opções disponíveis para os alunos</p>
          </div>
          
          {userRole !== 'cradt' && (
            <button 
                onClick={() => openModal()}
                className="bg-emerald-900 text-white px-4 py-2.5 rounded-2xl flex items-center gap-2 font-bold hover:bg-emerald-800 transition-all shadow-lg"
            >
                <Plus size={20} /> Novo Tipo
            </button>
          )}
        </div>

        <div className="bg-white rounded-[2rem] shadow-[0_20px_50px_rgba(0,0,0,0.05)] border border-slate-100 overflow-hidden">
            {loading ? (
                <div className="p-8 text-center text-gray-500">Carregando...</div>
            ) : (
                <table className="w-full text-left">
                    <thead className="bg-gray-50 text-gray-500 font-bold uppercase text-xs border-b border-gray-100">
                        <tr>
                            <th className="px-6 py-4">Nome</th>
                            <th className="px-6 py-4">Mensagem do Chat</th>
                            <th className="px-6 py-4">Exige Anexo?</th>
                            <th className="px-6 py-4 text-right">Ações</th>
                        </tr>
                    </thead>
                    <tbody className="divide-y divide-gray-50">
                        {types.map((type) => (
                            <tr key={type.id} className="hover:bg-blue-50/30 transition-colors">
                                <td className="px-6 py-4 font-bold text-gray-800">{type.name}</td>
                                <td className="px-6 py-4 text-gray-500 text-sm max-w-xs truncate">{type.description || "---"}</td>
                                <td className="px-6 py-4">
                                  {/* 🔹 Badge Atualizada: Sem triângulo e com o verde oficial do site */}
                                  <span className={`px-3 py-1 rounded-full text-xs font-bold uppercase tracking-wider ${
                                    type.requires_document 
                                      ? "bg-green-50 text-[#108542] border border-green-100" 
                                      : "bg-slate-100 text-slate-500"
                                  }`}>
                                    {type.requires_document ? "Sim" : "Não"}
                                  </span>
                                </td>
                                <td className="px-6 py-4 text-right flex justify-end gap-2">
                                    {userRole !== 'cradt' && (
                                        <>
                                            <button 
                                                onClick={() => openModal(type)}
                                                className="p-2 text-emerald-600 hover:bg-emerald-100 rounded-lg transition-colors"
                                            >
                                                <Edit size={18} />
                                            </button>
                                            <button 
                                                onClick={() => handleDelete(type.id)}
                                                className="p-2 text-red-600 hover:bg-red-100 rounded-lg transition-colors"
                                            >
                                                <Trash2 size={18} />
                                            </button>
                                        </>
                                    )}
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
                        <label className="block text-sm font-bold text-gray-700 mb-1">Nome do Requerimento</label>
                        <input 
                            type="text" 
                            value={formData.name}
                            onChange={(e) => setFormData({...formData, name: e.target.value})}
                            placeholder="Ex: Justificativa de Falta"
                            className="w-full px-4 py-3 bg-gray-50 border border-gray-200 rounded-xl focus:ring-2 focus:ring-emerald-500 outline-none transition-all"
                            required
                        />
                    </div>

                    <div>
                        <label className="block text-sm font-bold text-gray-700 mb-1">Mensagem Inicial (Pergunta do Robô)</label>
                        <textarea 
                            value={formData.description}
                            onChange={(e) => setFormData({...formData, description: e.target.value})}
                            placeholder="Ex: Descreva detalhadamente o motivo da sua solicitação:"
                            className="w-full px-4 py-3 bg-gray-50 border border-gray-200 rounded-xl focus:ring-2 focus:ring-emerald-500 outline-none transition-all h-20 resize-none"
                            required
                        />
                    </div>

                    {/* 🔮 SELETOR EM BOTÕES: PRECISA DE DOCUMENTO? */}
                    <div>
                        <label className="block text-sm font-bold text-gray-700 mb-2">Este requerimento exige documento?</label>
                        <div className="grid grid-cols-2 gap-3">
                            <button
                                type="button"
                                onClick={() => setFormData({...formData, requires_document: false})}
                                className={`py-3 rounded-xl font-bold text-sm border transition-all ${!formData.requires_document ? "bg-slate-100 border-slate-300 text-slate-700" : "bg-white border-gray-200 text-slate-400 hover:bg-gray-50"}`}
                            >
                                Não precisa
                            </button>
                            <button
                                type="button"
                                onClick={() => setFormData({...formData, requires_document: true})}
                                className={`py-3 rounded-xl font-bold text-sm border flex items-center justify-center gap-2 transition-all ${formData.requires_document ? "bg-amber-50 border-amber-400 text-amber-700 shadow-sm" : "bg-white border-gray-200 text-slate-400 hover:bg-gray-50"}`}
                            >
                                <FileCheck2 size={16} /> Precisa de Anexo
                            </button>
                        </div>
                    </div>

                    {/* 🔮 CAMPO CONDICIONAL: APARECE SE FOR "SIM" */}
                    {formData.requires_document && (
                        <div className="animate-in slide-in-from-top-2 duration-200">
                            <label className="block text-sm font-bold text-amber-800 mb-1">Quais documentos são necessários?</label>
                            <textarea 
                                value={formData.document_instructions}
                                onChange={(e) => setFormData({...formData, document_instructions: e.target.value})}
                                placeholder="Ex: Anexe o atestado médico digitalizado ou o laudo carimbado."
                                className="w-full px-4 py-3 bg-amber-50/50 border border-amber-200 rounded-xl focus:ring-2 focus:ring-emerald-500 outline-none transition-all h-20 resize-none text-sm text-slate-700"
                                required={formData.requires_document}
                            />
                        </div>
                    )}
                    
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
                            className="flex-1 px-4 py-3 bg-emerald-600 text-white font-bold rounded-xl hover:bg-emerald-700 flex justify-center items-center gap-2 transition-colors shadow-lg"
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