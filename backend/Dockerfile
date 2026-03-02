FROM golang:1.22-alpine AS build
WORKDIR /app
COPY . .
RUN go mod tidy && go build -o api ./cmd/api

FROM alpine:3.20
WORKDIR /app
COPY --from=build /app/api ./api
EXPOSE 8080
CMD ["./api"]
