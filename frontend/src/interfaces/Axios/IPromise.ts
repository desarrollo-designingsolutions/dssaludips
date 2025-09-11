import IErrorsBack from "./IErrorsBack"

export default interface IPromise {
  code: number,
  message: string,
  errors?: IErrorsBack
  data?: object
}
