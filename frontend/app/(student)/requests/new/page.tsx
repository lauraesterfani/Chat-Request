"use client";

import { useState, useEffect, useMemo } from "react";
import { useRouter } from "next/navigation";
import { X, AlertCircle, Paperclip } from "lucide-react";

const API_BASE = "/api";

// MAPA DE DOCUMENTOS (Baseado na sua lista de itens a-j)
const DOCUMENTOS_NECESSARIOS: Record<string, string> = {
  "justificativa": "Atestado Médico (a), Declaração da Empresa (d) ou Ementas (i).",
  "transferência": "Declaração de Transferência (c), Históricos (f, g ou h) e Ementas (i).",
  "isenção": "Histórico Escolar (f, g ou h) e Ementas das disciplinas (i).",
  "educação física": "Atestado Médico (a) ou Declaração de Unidade Militar (j).",
  "colação": "Atestado Médico (a) ou Cópia da CTPS (b) e Declaração da Empresa (d).",
  "transferência de turno": "Atestado Médico (a) ou Declaração de Unidade Militar (j).",
  "análise curricular": "Declaração de Transferência (c), Históricos e Ementas (i)."
};

export default function NewRequestPage() {
  const router = useRouter();

  // Estados dos Campos
  const [subject, setSubject] = useState("");
  const [description, setDescription] = useState("");
  const [typeId, setTypeId] = useState("");
  const [file, setFile] = useState<File | null>(null);
  const [loading, setLoading] = useState(false);
  const [requestTypes, setRequestTypes] = useState<any[]>([]);
  const [errorMsg, setErrorMsg] = useState("");

  // 1. Lógica de Identificação de Anexo (Reforçada)
  const infoAnexo = useMemo(() => {
    const selectedType = requestTypes.find(t => String(t.id) === String(typeId));
    if (!selectedType) return { requires: false, doc: "" };

    const name = selectedType.name.toLowerCase();
    
    // Procura no mapa se o nome do requerimento exige documentos
    const chaveEncontrada = Object.keys(DOCUMENTOS_NECESSARIOS).find(key => name.includes(key));
    
    return {
      requires: !!chaveEncontrada || name.includes("anexo"),
      doc: chaveEncontrada ? DOCUMENTOS_NECESSARIOS[chaveEncontrada] : "documento comprobatório"
    };
  }, [typeId, requestTypes]);

  const requiresFile = infoAnexo.requires;

  // 2. Carrega tipos de requerimento
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

          const savedId = localStorage.getItem('selected_type_id');
          if (savedId) {
            setTypeId(savedId);
            localStorage.removeItem('selected_type_id'); 
          }
        }
      } catch (error) {
        setErrorMsg("Erro de conexão com o servidor.");
      }
    };
    fetchData();
  }, []);

  // 3. Função de Envio com BLOQUEIO
  const handleSubmit = async (e: React.FormEvent) => {
    e.preventDefault();
    setErrorMsg("");

    // TRAVA CRÍTICA: Se precisa de arquivo e o estado 'file' está vazio, cancela o envio
    if (requiresFile && !file) {
      setErrorMsg(`Ei, você esqueceu de anexar o documento! Para este pedido, preciso de: ${infoAnexo.doc}`);
      // Rola para o erro para garantir que o usuário veja
      window.scrollTo({ top: 0, behavior: 'smooth' });
      return; 
    }

    setLoading(true);

    try {
      const token = localStorage.getItem("jwt_token");
      const formData = new FormData();
      formData.append("type_request_id", typeId);
      formData.append("subject", subject);
      formData.append("description", description);
      if (file) formData.append("file", file);

      const res = await fetch(`${API_BASE}/requests`, {
        method: "POST",
        headers: { Authorization: `Bearer ${token}` },
        body: formData
      });

      if (res.ok) {
        router.push("/me");
      } else {
        const errData = await res.json();
        setErrorMsg(errData.message || "Erro ao enviar requerimento.");
      }
    } catch (error) {
      setErrorMsg("Falha na comunicação com o servidor.");
    } finally {
      setLoading(false);
    }
  };

  return (
    <div className="min-h-screen bg-gray-50 py-12 px-4 font-sans">
      <div className="max-w-2xl mx-auto bg-white rounded-2xl shadow-xl overflow-hidden border border-gray-100">
        
        <div className="bg-[#108542] px-8 py-6">
          <h1 className="text-2xl font-bold text-white">Novo Requerimento</h1>
          <p className="text-green-100 text-sm opacity-90">Preencha os dados abaixo para sua solicitação.</p>
        </div>

        <div className="p-8">
          {/* Alerta de Erro Visual */}
          {errorMsg && (
            <div className="mb-6 p-4 bg-red-50 border-l-4 border-red-500 text-red-700 flex items-center gap-3 animate-pulse">
              <AlertCircle size={20} />
              <p className="text-sm font-bold">{errorMsg}</p>
            </div>
          )}

          <form onSubmit={handleSubmit} className="space-y-6">
            
            {/* Tipo de Solicitação */}
            <div>
              <label className="block text-sm font-semibold text-gray-700 mb-2">Tipo de Solicitação</label>
              <select 
                className="block w-full px-4 py-3 border-gray-300 rounded-lg border focus:ring-green-500 transition-all"
                value={typeId}
                onChange={(e) => {
                  setTypeId(e.target.value);
                  setErrorMsg(""); // Limpa erro ao mudar
                }}
                required
              >
                <option value="">Selecione uma opção...</option>
                {requestTypes.map((t) => <option key={t.id} value={t.id}>{t.name}</option>)}
              </select>
            </div>

            {/* Assunto */}
            <div>
               <label className="block text-sm font-semibold text-gray-700 mb-2">Assunto</label>
               <input 
                 type="text" 
                 className="block w-full px-4 py-3 rounded-lg border-gray-300 border focus:ring-green-500" 
                 value={subject} 
                 onChange={(e) => setSubject(e.target.value)} 
                 required 
               />
            </div>

            {/* Descrição */}
            <div>
               <label className="block text-sm font-semibold text-gray-700 mb-2">Descrição Detalhada</label>
               <textarea 
                 className="block w-full px-4 py-3 rounded-lg border-gray-300 border h-32 resize-none focus:ring-green-500" 
                 value={description} 
                 onChange={(e) => setDescription(e.target.value)} 
                 required 
               />
            </div>

            {/* Área de Anexo Dinâmica */}
            {requiresFile && (
              <div className={`rounded-xl border-2 border-dashed p-6 transition-all ${!file && errorMsg ? 'border-red-500 bg-red-50' : 'border-yellow-400 bg-yellow-50'}`}>
                <div className="flex items-center gap-2 mb-2">
                  <Paperclip size={18} className="text-gray-600" />
                  <label className="block text-sm font-bold text-gray-800">Anexo Obrigatório</label>
                </div>
                <p className="text-xs text-gray-600 mb-3 italic">Necessário: {infoAnexo.doc}</p>
                <input 
                  type="file" 
                  onChange={(e) => {
                    setFile(e.target.files?.[0] || null);
                    setErrorMsg("");
                  }} 
                  className="block w-full text-sm text-slate-500 file:mr-4 file:py-2 file:px-4 file:rounded-full file:border-0 file:text-sm file:font-semibold file:bg-green-50 file:text-green-700 hover:file:bg-green-100"
                />
              </div>
            )}

            {/* Botões */}
            <div className="flex gap-4 pt-4">
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
                className="flex-[2] bg-[#108542] text-white font-bold py-4 rounded-xl shadow-lg hover:bg-[#0d6e36] transition-all disabled:opacity-50"
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