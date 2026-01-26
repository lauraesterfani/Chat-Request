"use client";

import { useState } from "react";
import Link from "next/link";
import { Eye, EyeOff, Lock, CreditCard } from "lucide-react"; // Ícones bonitos e leves

export default function LoginPage() {
  const [showPassword, setShowPassword] = useState(false);
  const [cpf, setCpf] = useState("");
  const [password, setPassword] = useState("");

  return (
    <div className="min-h-screen bg-white flex flex-col items-center justify-center p-4 font-sans">
      
      {/* Botões de navegação superior (Voltar / Sou Servidor) */}
      <div className="fixed top-0 w-full max-w-5xl flex justify-between p-6 text-sm font-medium text-gray-400">
        <Link href="/" className="hover:text-gray-600 flex items-center gap-1">
          <span>◀</span> Voltar
        </Link>
        <Link href="/login-admin" className="hover:text-gray-600 uppercase tracking-widest text-[10px]">
          Sou Servidor
        </Link>
      </div>

      <div className="w-full max-w-[400px] flex flex-col items-center">
        
        {/* Logo Estilizada */}
        <div className="flex items-center gap-2 mb-8">
          <div className="bg-[#108542] text-white font-bold p-1.5 rounded-lg text-lg px-2.5">CR</div>
          <span className="text-2xl font-bold text-gray-800 tracking-tight">ChatRequest</span>
        </div>

        <h1 className="text-3xl font-black text-[#0f172a] mb-2">Login do Aluno</h1>
        <p className="text-gray-400 mb-10">Entre com seu CPF e senha.</p>

        <div className="w-full space-y-6">
          
          {/* Campo CPF */}
          <div className="space-y-2">
            <label className="text-sm font-bold text-gray-700 ml-1">CPF</label>
            <div className="relative group">
              <div className="absolute left-4 top-1/2 -translate-y-1/2 text-gray-400 group-focus-within:text-[#108542] transition-colors">
                <CreditCard size={20} />
              </div>
              <input
                type="text"
                placeholder="000.000.000-00"
                className="w-full bg-[#f8fafc] border border-gray-100 rounded-2xl py-4 pl-12 pr-4 outline-none focus:ring-2 focus:ring-[#108542]/20 focus:border-[#108542] transition-all text-gray-600 placeholder:text-gray-300"
                value={cpf}
                onChange={(e) => setCpf(e.target.value)}
              />
            </div>
          </div>

          {/* Campo Senha */}
          <div className="space-y-2">
            <div className="flex justify-between items-center px-1">
              <label className="text-sm font-bold text-gray-700">Senha</label>
              <Link href="/forgot" className="text-xs font-bold text-[#108542] hover:underline">Esqueceu?</Link>
            </div>
            <div className="relative group">
              <div className="absolute left-4 top-1/2 -translate-y-1/2 text-gray-400 group-focus-within:text-[#108542] transition-colors">
                <Lock size={20} />
              </div>
              <input
                type={showPassword ? "text" : "password"}
                placeholder="••••••••"
                className="w-full bg-[#f8fafc] border border-gray-100 rounded-2xl py-4 pl-12 pr-12 outline-none focus:ring-2 focus:ring-[#108542]/20 focus:border-[#108542] transition-all text-gray-600 placeholder:text-gray-300"
                value={password}
                onChange={(e) => setPassword(e.target.value)}
              />
              {/* Botão de Visualizar Senha */}
              <button
                type="button"
                onClick={() => setShowPassword(!showPassword)}
                className="absolute right-4 top-1/2 -translate-y-1/2 text-gray-400 hover:text-gray-600 transition-colors"
              >
                {showPassword ? <EyeOff size={20} /> : <Eye size={20} />}
              </button>
            </div>
          </div>

          {/* Botão de Entrar */}
          <button className="w-full bg-[#108542] text-white font-black py-4 rounded-2xl shadow-lg shadow-green-100 hover:bg-[#0d6e36] hover:scale-[1.02] active:scale-[0.98] transition-all mt-4">
            Entrar na Plataforma
          </button>

          <p className="text-center text-sm text-gray-400 mt-8">
            Ainda não tem acesso? <Link href="/register" className="text-[#108542] font-black hover:underline">Criar conta agora</Link>
          </p>
        </div>
      </div>
    </div>
  );
}