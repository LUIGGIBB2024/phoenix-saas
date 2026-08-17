export default createVuetify({
  locale: {
    locale: 'es', // Idioma por defecto
    fallback: 'en', // Idioma secundario si falta una traducción
    messages: { es },
  },

  // Tu configuración previa de date adapters (si aplica)
})

// utils/date.ts
export const getDefaultProcessYear = () => new Date().getFullYear() + 2
