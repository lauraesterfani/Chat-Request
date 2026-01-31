"use client";

import { useState, useEffect, useMemo } from "react";
import { useRouter } from "next/navigation";
import { X } from "lucide-react";

const API_BASE = "http://127.0.0.1:8000/api";

export default function NewRequestPage() {
  const router = useRouter();

  // Estados dos Campos (Mantendo como estavam)
  const [subject, setSubject] = useState("");
  const [description, setDescription] = useState("");
  const [typeId, setTypeId] = useState("");
  const [file, setFile] = useState<File | null>(null);
  const [loading, setLoading] = useState(false);
  const [requestTypes, setRequestTypes] = useState<any[]>([]);
  const [errorMsg, setErrorMsg] = useState("");

  // 1. Lógica de Identificação de Anexo (Original)
  const requiresFile = useMemo(() => {
    const selectedType = requestTypes.find(t => String(t.id) === String(typeId));
    if (!selectedType) return false;
    const name = selectedType.name.toLowerCase();
    const typesWithAnexo = [
      "aproveitamento", "isenção", "justificativa", "falta", 
      "chamada", "educação física", "transferência", "cancelamento"
    ];
    return typesWithAnexo.some(keyword => name.includes(keyword));
  }, [typeId, requestTypes]);

  // 2. Carrega tipos e faz a SELEÇÃO AUTOMÁTICA via localStorage
  useEffect(() => {
    const fetchData = async () => {
      const token = localStorage.getItem("jwt_token");
      try {
        const res = await fetch(`${API_BASE}/type-requests`, {
          headers: { Authorization: `Bearer ${token}` }
        });
        if (res.ok) {
          const data = await res.json();
          setRequestTypes(data);

          // PEGA O ID QUE A SUA HOME SALVOU
          const savedId = localStorage.getItem('selected_type_id');
          if (savedId) {
            setTypeId(savedId);
            // Limpa para não selecionar sozinho na próxima vez que entrar direto
            localStorage.removeItem('selected_type_id'); 
          }
        }
      } catch (error) {
        setErrorMsg("Erro de conexão com o servidor.");
      }
    };
    fetchData();
  }, []);

  const handleSubmit = async (e: React.FormEvent) => {
    e.preventDefault();
    // ... sua lógica de envio (omitida para brevidade, mantenha a sua original)
  };

  return (
    <div className="min-h-screen bg-gray-50 py-12 px-4 font-sans">
      <div className="max-w-2xl mx-auto bg-white rounded-2xl shadow-xl overflow-hidden border border-gray-100">
        
        <div className="bg-[#108542] px-8 py-6">
          <h1 className="text-2xl font-bold text-white">Novo Requerimento</h1>
          <p className="text-green-100 text-sm opacity-90">Preencha os dados abaixo para sua solicitação.</p>
        </div>

        <div className="p-8">
          <form onSubmit={handleSubmit} className="space-y-6">
            
            {/* Campo Tipo (Onde a seleção automática aparece) */}
            <div>
              <label className="block text-sm font-semibold text-gray-700 mb-2">Tipo de Solicitação</label>
              <select 
                className="block w-full px-4 py-3 border-gray-300 rounded-lg border focus:ring-green-500 transition-all"
                value={typeId}
                onChange={(e) => setTypeId(e.target.value)}
                required
              >
                <option value="">Selecione uma opção...</option>
                {requestTypes.map((t) => <option key={t.id} value={t.id}>{t.name}</option>)}
              </select>
            </div>

            {/* Campo Assunto */}
            <div>
               <label className="block text-sm font-semibold text-gray-700 mb-2">Assunto</label>
               <input 
                 type="text" 
                 className="block w-full px-4 py-3 rounded-lg border-gray-300 border focus:ring-green-500 transition-all" 
                 placeholder="Ex: Correção de nota"
                 value={subject} 
                 onChange={(e) => setSubject(e.target.value)} 
                 required 
               />
            </div>

            {/* Campo Descrição */}
            <div>
               <label className="block text-sm font-semibold text-gray-700 mb-2">Descrição Detalhada</label>
               <textarea 
                 className="block w-full px-4 py-3 rounded-lg border-gray-300 border h-32 resize-none focus:ring-green-500 transition-all" 
                 placeholder="Descreva sua solicitação..."
                 value={description} 
                 onChange={(e) => setDescription(e.target.value)} 
                 required 
               />
            </div>

            {/* Área de Anexo (Apenas se necessário) */}
            {requiresFile && (
              <div className="rounded-xl border-2 border-dashed border-yellow-400 bg-yellow-50 p-6">
                <label className="block text-sm font-bold text-gray-800 mb-1">Anexo Obrigatório</label>
                <input 
                  type="file" 
                  onChange={(e) => setFile(e.target.files?.[0] || null)} 
                  className="mt-2 block w-full text-sm text-slate-500"
                  required
                />
              </div>
            )}

            {/* Botões de Ação */}
            <div className="flex gap-4 pt-4">
              {/* BOTÃO CANCELAR: Vai para a página principal /me */}
              <button
                type="button"
                onClick={() => router.push('/me')} 
                className="flex-1 bg-gray-100 text-gray-600 font-bold py-4 rounded-xl hover:bg-gray-200 transition-all flex items-center justify-center gap-2"
              >
                <X size={18} /> Cancelar
              </button>
              
              <button
                type="submit"
                disabled={loading}
                className="flex-[2] bg-[#108542] text-white font-bold py-4 rounded-xl shadow-lg hover:bg-[#0d6e36] transition-all"
              >
                {loading ? "Enviando..." : "Enviar Requerimento"}
              </button>
            </div>
          </form>
        </div>
      </div>
    </div>
  );
}