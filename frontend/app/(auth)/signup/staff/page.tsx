"use client";

import { useEffect, useState } from "react";
import { useRouter } from "next/navigation";

interface Course {
  id: string;
  name: string;
}

export default function StaffSignupPage() {
  const router = useRouter();
  const [courses, setCourses] = useState<Course[]>([]);
  const [loading, setLoading] = useState(false);

  const [formData, setFormData] = useState({
    name: "",
    email: "",
    cpf: "",
    phone: "",
    matricula: "",
    course_id: "",
    birthday: "",
  });

  useEffect(() => {
    fetch("http://localhost:8000/api/courses")
      .then(res => res.json())
      .then(data => setCourses(data))
      .catch(() => alert("Erro ao carregar cursos"));
  }, []);

  function handleChange(
    e: React.ChangeEvent<HTMLInputElement | HTMLSelectElement>
  ) {
    const { name, value } = e.target;
    setFormData(prev => ({ ...prev, [name]: value }));
  }

  async function handleSubmit(e: React.FormEvent) {
    e.preventDefault();
    setLoading(true);

    try {
      const res = await fetch("http://localhost:8000/api/staff/register", {
        method: "POST",
        headers: {
          "Content-Type": "application/json",
        },
        body: JSON.stringify(formData),
      });

      const data = await res.json();

      if (!res.ok) {
        alert(data.message || "Erro ao cadastrar staff");
        return;
      }

      alert("Staff cadastrado com sucesso!");
      router.push("/dashboard");
    } catch (error) {
      alert("Erro inesperado");
    } finally {
      setLoading(false);
    }
  }

  return (
    <div className="min-h-screen flex bg-[#fcfcfc]">
      {/* Painel esquerdo */}
      <aside className="w-64 bg-slate-900 text-white flex flex-col p-6">
        <h1 className="text-2xl font-bold mb-4 tracking-tighter">Chat Request</h1>
        <p className="text-sm opacity-80">
          Cadastro de membros da equipe
        </p>
      </aside>

      {/* Conteúdo */}
      <main className="flex-1 p-10">
        <h2 className="text-2xl font-semibold mb-6 text-slate-800 tracking-tighter">
          Cadastrar novo staff
        </h2>

        <form
          onSubmit={handleSubmit}
          className="bg-white rounded-[2rem] shadow-[0_20px_50px_rgba(0,0,0,0.05)] border border-slate-100 p-8 max-w-xl space-y-4"
        >
          <input
            name="name"
            placeholder="Nome completo"
            className="w-full border border-slate-200 bg-slate-50 rounded-2xl p-3 outline-none focus:border-[#108542] focus:ring-2 focus:ring-emerald-100"
            onChange={handleChange}
            required
          />

          <input
            name="email"
            type="email"
            placeholder="Email institucional"
            className="w-full border border-slate-200 bg-slate-50 rounded-2xl p-3 outline-none focus:border-[#108542] focus:ring-2 focus:ring-emerald-100"
            onChange={handleChange}
            required
          />

          <input
            name="cpf"
            placeholder="CPF"
            className="w-full border border-slate-200 bg-slate-50 rounded-2xl p-3 outline-none focus:border-[#108542] focus:ring-2 focus:ring-emerald-100"
            onChange={handleChange}
            required
          />

          <input
            name="phone"
            placeholder="Telefone"
            className="w-full border border-slate-200 bg-slate-50 rounded-2xl p-3 outline-none focus:border-[#108542] focus:ring-2 focus:ring-emerald-100"
            onChange={handleChange}
            required
          />

          <input
            name="matricula"
            placeholder="Matrícula"
            className="w-full border border-slate-200 bg-slate-50 rounded-2xl p-3 outline-none focus:border-[#108542] focus:ring-2 focus:ring-emerald-100"
            onChange={handleChange}
            required
          />

          <select
            name="course_id"
            className="w-full border border-slate-200 bg-slate-50 rounded-2xl p-3 outline-none focus:border-[#108542] focus:ring-2 focus:ring-emerald-100"
            onChange={handleChange}
            required
          >
            <option value="">Selecione o curso</option>
            {courses.map(course => (
              <option key={course.id} value={course.id}>
                {course.name}
              </option>
            ))}
          </select>

          <input
            type="date"
            name="birthday"
            className="w-full border border-slate-200 bg-slate-50 rounded-2xl p-3 outline-none focus:border-[#108542] focus:ring-2 focus:ring-emerald-100"
            onChange={handleChange}
            required
          />

          <button
            type="submit"
            disabled={loading}
            className="w-full bg-slate-900 text-white rounded-2xl p-3 font-bold hover:bg-slate-800 transition"
          >
            {loading ? "Cadastrando..." : "Cadastrar staff"}
          </button>
        </form>
      </main>
    </div>
  );
}
