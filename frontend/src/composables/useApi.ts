import { router } from "@/plugins/1.router";
import { createFetch } from "@vueuse/core";
import { destr } from "destr";
const { toast } = useToast();

export const useApi = createFetch({
  baseUrl: import.meta.env.VITE_API_BASE_URL || "/api",
  fetchOptions: {
    headers: {
      "Content-Type": "application/json",
    },
  },
  options: {
    updateDataOnError: true,
    async onFetchError(ctx) {

      const data = JSON.parse(ctx.data)

      ctx.data = data// Modifies the response data 

      //verifica que tenga autorizacion apra acceder a la vista
      if (ctx.response?.status == 403 || data.code == 403) {
        router.push({ name: "NotAuthorized" })
        return ctx
      }


      if (ctx.data.message) toast("Error", ctx.data.message, "danger");
      return ctx;
    },

    refetch: false,
    async beforeFetch({ options }) {
      const accessToken = useCookie("accessToken").value;

      if (accessToken) {
        options.headers = {
          ...options.headers,
          Authorization: `Bearer ${accessToken}`,
        };
      }

      // Añade lógica para verificar si los datos son FormData y establecer el encabezado correspondiente
      if (options.body instanceof FormData) {
        // No establezcas el encabezado Content-Type si son FormData
        delete options.headers["Content-Type"];
      }
      return { options };
    },
    afterFetch(ctx) {
      const { data, response } = ctx;
      // Parse data if it's JSON

      let parsedData: { code: string | number, message: string } | null = null;
      try {
        parsedData = destr(data);
      } catch (error) {
        console.error(error);
      }

      if (parsedData && parsedData.message) {
        //verifica que tenga autorizacion apra acceder a la vista
        if (parsedData.code == 403) {
          router.push({ name: "NotAuthorized" })
          return false
        }

        if (parsedData.code != 200) {
          toast("Error", parsedData.message, "danger");
        } else {
          toast("Exito", parsedData.message, "success");
        }
      }
      return { data: parsedData, response };
    },
  },
});
