"use client";

import { useAuth } from "../context/AuthContext";
import { useRouter } from "next/navigation";
import { useEffect, useRef, useState } from "react";
import Link from "next/link";
import { HelpCircle, X, MessageSquare, FileText, CheckCircle, LogOut, ChevronDown } from "lucide-react";

export default function RootLayout({ children }: { children: React.ReactNode }) {
  const { user, isAuthenticated, logout } = useAuth();
  const router = useRouter();
  const [open, setOpen] = useState(false);
  const [isHelpOpen, setIsHelpOpen] = useState(false);
  const dropdownRef = useRef<HTMLDivElement>(null);

  useEffect(() => {
    function handleClickOutside(event: MouseEvent) {
      if (dropdownRef.current && !dropdownRef.current.contains(event.target as Node)) {
        setOpen(false);
      }
    }
    document.addEventListener("mousedown", handleClickOutside);
    return () => document.removeEventListener("mousedown", handleClickOutside);
  }, []);

  const isAdmin = ["admin", "staff", "cradt"].includes(user?.role ?? "");

  return (
    <div className="bg-[#fcfcfc] min-h-screen antialiased font-sans text-slate-700">

      {/* 🔹 NAVBAR CONDICIONAL */}
      <header
        className={`w-full px-6 sm:px-8 py-5 flex items-center justify-between border-b ${
          isAdmin
            ? "bg-[#0B0D3A] text-white border-[#0B0D3A]" // Admin → azul escuro
            : "bg-white text-slate-700 border-slate-100" // Aluno → branco
        }`}
      >
        {/* Logo / Título */}
        <Link
          href={isAdmin ? "/dashboard/admin" : "/me"} // 🔹 Admin → tela principal CRADT
          className={`tracking-tighter hover:opacity-80 transition-opacity ${
            isAdmin ? "text-2xl font-bold" : "text-3xl font-bold mb-4 text-[#108542]"
          }`}
        >
          {isAdmin ? "Painel Administrativo" : "Chat Request"}
        </Link>

        {isAuthenticated && user && (
          <div className="flex items-center gap-4">
            {/* ❓ BOTÃO DE AJUDA (somente aluno) */}
            {!isAdmin && (
              <button
                onClick={() => setIsHelpOpen(true)}
                className="p-2 text-slate-400 hover:text-[#108542] transition-colors"
                title="Como usar o sistema"
              >
                <HelpCircle size={22} strokeWidth={1.5} />
              </button>
            )}

            {/* Menu do Usuário */}
            <div className="relative" ref={dropdownRef}>
              <button
                onClick={() => setOpen(!open)}
                className={`flex items-center gap-2 ${
                  isAdmin ? "text-white" : "text-slate-700"
                } font-bold text-lg group`} 
              >
                {/* Nome do aluno em verde */}
                <span className="text-[#108542]">{user.name}</span>
                <ChevronDown
                  size={20}
                  className={`transition-transform duration-300 ${open ? "rotate-180" : ""}`}
                />
              </button>

              {open && (
                <div className="absolute right-0 mt-4 w-72 bg-white border border-slate-100 rounded-[2rem] shadow-[0_20px_50px_rgba(0,0,0,0.05)] p-7 z-50 animate-in fade-in zoom-in-95 duration-200">
                  <div className="mb-5">
                    <p className="text-[10px] font-normal text-slate-400 uppercase tracking-widest mb-2 ml-1">CPF</p>
                    <div className="w-full px-5 py-3.5 bg-slate-50 border border-slate-100 rounded-2xl text-slate-700 font-bold text-sm italic">
                      {user.cpf}
                    </div>
                  </div>

                  <div className="mb-7">
                    <p className="text-[10px] font-normal text-slate-400 uppercase tracking-widest mb-2 ml-1">Telefone</p>
                    <div className="w-full px-5 py-3.5 bg-slate-50 border border-slate-100 rounded-2xl text-slate-700 font-bold text-sm italic">
                      {user.phone}
                    </div>
                  </div>

                  {/* 🔹 Botão de sair em vermelho com texto branco */}
                  <button
                    onClick={() => { logout(); router.push("/login"); }}
                    className="w-full py-4 bg-red-600 text-white text-[11px] font-bold uppercase tracking-[0.2em] rounded-2xl hover:bg-red-700 transition-all active:scale-95 shadow-lg flex items-center justify-center gap-2"
                  >
                    <LogOut size={14} /> Sair do Sistema
                  </button>
                </div>
              )}
            </div>
          </div>
        )}
      </header>

      {/* 🔹 CONTEÚDO */}
      <main className="max-w-7xl mx-auto p-4 sm:p-6">{children}</main>

      {/* 🔹 MODAL DE AJUDA (somente aluno) */}
      {!isAdmin && isHelpOpen && (
        <div className="fixed inset-0 z-[100] bg-white/60 backdrop-blur-md flex items-center justify-center p-6 animate-in fade-in duration-300">
          <div className="max-w-xs w-full text-center">
            <button
              onClick={() => setIsHelpOpen(false)}
              className="absolute top-10 right-10 text-slate-400 hover:text-black transition-colors"
            >
              <X size={24} strokeWidth={1.5} />
            </button>

            <img src="/mascote_simples.png" className="h-20 mx-auto mb-8 opacity-90" alt="Stella" />

            <h3 className="text-xl font-bold tracking-tighter mb-4 text-slate-800">Como funciona?</h3>

            <div className="space-y-6 text-left mb-10">
              <div className="flex gap-4">
                <MessageSquare className="text-green-600 shrink-0" size={18} />
                <p className="text-[13px] text-slate-700 leading-relaxed font-bold">Escolha entre abrir um novo pedido ou consultar seu histórico.</p>
              </div>
              <div className="flex gap-4">
                <FileText className="text-green-600 shrink-0" size={18} />
                <p className="text-[13px] text-slate-700 leading-relaxed font-bold">Siga os passos da conversa. Eu vou te guiar em cada etapa.</p>
              </div>
              <div className="flex gap-4">
                <CheckCircle className="text-green-600 shrink-0" size={18} />
                <p className="text-[13px] text-slate-700 leading-relaxed font-bold">Se o sistema pedir, anexe o comprovante pelo ícone de clipe.</p>
              </div>
            </div>

            <button
              onClick={() => setIsHelpOpen(false)}
              className="w-full py-4 bg-slate-900 text-white font-bold text-[11px] tracking-[0.2em] uppercase rounded-2xl shadow-xl active:scale-95 transition-all"
            >
              Entendido
            </button>
          </div>
        </div>
      )}
    </div>
  );
}
