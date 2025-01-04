import { createStore } from 'vuex';

export const store = createStore({
    state: {
        isAuthenticated: false,
        user: null,
    },
    mutations: {
        setUser(state, user) {
            state.isAuthenticated = true;
            state.user = user;
        },
        logout(state) {
            state.isAuthenticated = false;
            state.user = null;
        },
    },
    actions: {
        login({ commit }, user) {
            commit('setUser', user);
            return Promise.resolve();
        },
        logout({ commit }) {
            commit('logout');
            return Promise.resolve();
        },
    },
    getters: {
        isAuthenticated: (state) => state.isAuthenticated,
        user: (state) => state.user,
    },
});
