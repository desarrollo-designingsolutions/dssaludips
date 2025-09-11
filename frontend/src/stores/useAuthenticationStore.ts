import ILogin from "@/interfaces/Authentication/ILogin";
import IPromise from "@/interfaces/Axios/IPromise";

export const useAuthenticationStore = defineStore("useAuthenticationStore", {
  state: () => ({
    isAuthenticate: false,
    tokenGoogle: "",
    access_token: "",
    user: {},
    company: { id: null },
    menu: [],
    permissions: [],
    loading: false,
    rememberMe: false,
  }),
  persist: true,
  getters: {
    getMenuData: (state) => {
      if (!isEmpty(state.company.id))
        return state.menu.filter((ele) => ele.to.name != "Company-List");
      else return state.menu.filter((ele) => ele.to.name == "Company-List");
    },
  },
  actions: {
    async logout() {
      this.$reset();
    },
    async login(formulario: ILogin): Promise<IPromise> {
      this.loading = true;
      const { data, response } = await useAxios("/login").post(formulario);

      this.loading = false;

      if (response.status == 200 && data) {
        this.isAuthenticate = true;
        this.user = data.user;
        this.company = data.company;
        this.menu = data.menu;
        this.permissions = data.permissions;
        this.access_token = data.access_token;
        useCookie("accessToken").value = this.access_token;
      }

      return data;

    },
    checkAuthentication() {
      const isAuthenticated = !!this.access_token;
      return isAuthenticated && this.rememberMe;
    },
  },
});
