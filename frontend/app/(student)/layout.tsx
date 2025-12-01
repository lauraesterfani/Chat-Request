"use client";

import { useAuth } from '../context/AuthContext';
import { useRouter } from "next/navigation";
import { useEffect, useRef, useState } from "react";
import Link from "next/link";

export default function RootLayout({ children }: { children: React.ReactNode }) {
  const { user, isAuthenticated, logout } = useAuth();
  const router = useRouter();
  const [open, setOpen] = useState(false);
  const dropdownRef = useRef<HTMLDivElement>(null);

  // Fecha o dropdown ao clicar fora
  useEffect(() => {
    function handleClickOutside(event: MouseEvent) {
      if (dropdownRef.current && !dropdownRef.current.contains(event.target as Node)) {
        setOpen(false);
      }
    }
    document.addEventListener("mousedown", handleClickOutside);
    return () => document.removeEventListener("mousedown", handleClickOutside);
  }, []);

  return (
    <div className="bg-gray-50 min-h-screen">

      {/* 🔹 TOPO DO SISTEMA */}
      <header className="w-full bg-white border-b px-8 py-4 flex items-center justify-between shadow-sm">

        {/* ◾ Logo/Nome */}
        <Link href="/me" className="text-2xl font-bold text-[#15803d]">
          Chat Request
        </Link>

        {/* ◾ Nome do usuário + dropdown */}
        {isAuthenticated && user && (
          <div className="relative" ref={dropdownRef}>
            <button
              onClick={() => setOpen(!open)}
              className="flex items-center gap-1 text-gray-800 font-semibold hover:text-[#15803d] transition"
            >
              {user.name}
              <span className="text-sm">▼</span>
            </button>

            {open && (
              <div
                className="absolute right-0 mt-3 w-72 bg-white border border-gray-200 rounded-2xl shadow-xl p-5 
                           animate-[fadeIn_0.15s_ease-out] z-50"
              >
                {/* CPF */}
                <div className="mb-4">
                  <p className="text-xs font-semibold text-gray-500 mb-1 ml-1">CPF</p>
                  <div className="w-full px-4 py-3 bg-gray-50 border border-gray-200 rounded-xl text-gray-800 font-medium">
                    {user.cpf}
                  </div>
                </div>

                {/* TELEFONE */}
                <div className="mb-5">
                  <p className="text-xs font-semibold text-gray-500 mb-1 ml-1">Telefone</p>
                  <div className="w-full px-4 py-3 bg-gray-50 border border-gray-200 rounded-xl text-gray-800 font-medium">
                    {user.phone}
                  </div>
                </div>

                {/* SAIR */}
                <button
                  onClick={() => {
                    logout();
                    router.push("/login");
                  }}
                  className="w-full py-3 bg-red-500 text-white font-bold rounded-xl shadow-md 
                             hover:bg-red-600 active:scale-[0.98] transition"
                >
                  Sair
                </button>
              </div>
            )}
          </div>
        )}
      </header>

      {/* 🔹 Conteúdo das páginas */}
      <main className="p-6">{children}</main>

    </div>
  );
}
